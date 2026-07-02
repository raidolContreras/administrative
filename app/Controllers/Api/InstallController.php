<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\Setting;
use App\Models\User;
use App\Support\Modules;
use App\Support\Seeder;
use Core\Database;
use Core\Exceptions\HttpException;
use Core\Exceptions\ValidationException;
use Core\Migrator;
use Core\Request;
use Core\Response;
use Core\Validator;

/**
 * Instalador de primera ejecución (pensado para cPanel sin SSH):
 * prueba la conexión, escribe .env (solo si no existe), migra, siembra y crea el admin.
 * Se autodesactiva: en cuanto existe un usuario, responde 403.
 */
final class InstallController
{
    public function status(Request $request): Response
    {
        return Response::json([
            'installed' => $this->isInstalled(),
            'env_exists' => is_file(base_path('.env')),
        ]);
    }

    public function store(Request $request): Response
    {
        if ($this->isInstalled()) {
            throw HttpException::forbidden('El sistema ya está instalado.', 'ALREADY_INSTALLED');
        }

        $data = Validator::validate((array) $request->input(), [
            'app_name' => 'required|string|max:80',
            'db_host' => 'required|string|max:190',
            'db_port' => 'nullable|int|min:1|max:65535',
            'db_database' => ['required', 'max:64', 'regex:/^[A-Za-z0-9_]+$/'],
            'db_username' => 'required|string|max:190',
            'db_password' => 'nullable|string|max:190',
            'admin_name' => 'required|string|max:120',
            'admin_email' => 'required|email|max:190',
            'admin_password' => 'required|min:8|max:72',
        ]);
        foreach (['db_host', 'db_username', 'db_password'] as $field) {
            if (str_contains((string) ($data[$field] ?? ''), '"') || str_contains((string) ($data[$field] ?? ''), "\n")) {
                throw new ValidationException([$field => ['No se permiten comillas dobles ni saltos de línea.']]);
            }
        }

        $db = [
            'host' => $data['db_host'],
            'port' => (string) ($data['db_port'] ?? 3306),
            'database' => $data['db_database'],
            'username' => $data['db_username'],
            'password' => (string) ($data['db_password'] ?? ''),
        ];

        // 1) Probar conexión antes de escribir nada
        try {
            new \PDO(
                "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset=utf8mb4",
                $db['username'],
                $db['password'],
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_TIMEOUT => 5]
            );
        } catch (\PDOException $e) {
            throw new ValidationException(['db_host' => ['No se pudo conectar a la base de datos: ' . $e->getMessage()]]);
        }

        // 2) Escribir .env solo si no existe (nunca sobreescribir una instalación)
        if (!is_file(base_path('.env'))) {
            $this->writeEnv($data['app_name'], $db);
        }

        // 3) Reapuntar la config viva a la nueva BD y ejecutar migraciones + seeds
        $GLOBALS['__config']['db'] = $db;
        Database::purge();
        (new Migrator(Modules::migrationSources()))->migrate();
        Seeder::run('core');
        Setting::set('app_name', $data['app_name']);

        // 4) Primer administrador
        if (User::count() === 0) {
            User::create([
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password_hash' => password_hash($data['admin_password'], PASSWORD_DEFAULT),
                'role' => 'admin',
                'is_active' => 1,
            ]);
        }

        return Response::json(['installed' => true], 201);
    }

    private function isInstalled(): bool
    {
        try {
            return User::count() > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    private function writeEnv(string $appName, array $db): void
    {
        $appName = str_replace(['"', "\r", "\n"], '', $appName);
        $lines = [
            'APP_NAME="' . $appName . '"',
            'APP_ENV=production',
            'APP_DEBUG=false',
            'APP_TIMEZONE=' . config('app.timezone'),
            'ASSET_VERSION=',
            '',
            'DB_HOST=' . $db['host'],
            'DB_PORT=' . $db['port'],
            'DB_DATABASE=' . $db['database'],
            'DB_USERNAME=' . $db['username'],
            'DB_PASSWORD="' . $db['password'] . '"',
            '',
            'SESSION_NAME=adm_' . bin2hex(random_bytes(3)),
            'SESSION_IDLE_MINUTES=120',
            'SESSION_SECURE=auto',
        ];
        $ok = file_put_contents(base_path('.env'), implode(PHP_EOL, $lines) . PHP_EOL, LOCK_EX);
        if ($ok === false) {
            throw new \RuntimeException('No se pudo escribir el archivo .env (verifica permisos de escritura en la raíz).');
        }
    }
}
