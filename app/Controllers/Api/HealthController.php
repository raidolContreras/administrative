<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Support\Modules;
use Core\Auth;
use Core\Database;
use Core\Migrator;
use Core\Request;
use Core\Response;

/**
 * Diagnóstico del hosting: primera herramienta al desplegar en un cPanel nuevo.
 * Estado público; detalle (versiones/límites) solo en debug o para admin autenticado.
 */
final class HealthController
{
    private const EXTENSIONS = ['pdo_mysql', 'mbstring', 'fileinfo', 'json', 'openssl'];

    public function show(Request $request): Response
    {
        $missingExt = array_values(array_filter(self::EXTENSIONS, static fn (string $e): bool => !extension_loaded($e)));

        $storageIssues = [];
        foreach (['logs', 'sessions', 'uploads', 'cache'] as $dir) {
            $path = storage_path($dir);
            if (!is_dir($path) || !is_writable($path)) {
                $storageIssues[] = "storage/{$dir}";
            }
        }

        $dbOk = false;
        $dbError = null;
        $pendingMigrations = null;
        try {
            Database::pdo();
            $dbOk = true;
            $pendingMigrations = (new Migrator(Modules::migrationSources()))->pendingCount();
        } catch (\Throwable $e) {
            $dbError = $e::class;
        }

        $checks = [
            'php' => version_compare(PHP_VERSION, '8.2.0', '>='),
            'extensions' => $missingExt === [],
            'storage_writable' => $storageIssues === [],
            'database' => $dbOk,
            'migrations_al_dia' => $dbOk ? $pendingMigrations === 0 : false,
        ];
        $status = ($checks['php'] && $checks['extensions'] && $checks['storage_writable'] && $checks['database'])
            ? 'ok' : 'degraded';

        $payload = ['status' => $status, 'checks' => $checks];

        if ($this->canSeeDetail()) {
            $payload['detail'] = [
                'php_version' => PHP_VERSION,
                'extensiones_faltantes' => $missingExt,
                'storage_sin_escritura' => $storageIssues,
                'db_error' => $dbError,
                'migraciones_pendientes' => $pendingMigrations,
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
                'memory_limit' => ini_get('memory_limit'),
                'app_version' => (string) config('app.version'),
                'modulos_activos' => Modules::activeNames(),
            ];
        }

        return Response::json($payload, $status === 'ok' ? 200 : 503);
    }

    private function canSeeDetail(): bool
    {
        if (config('app.debug')) {
            return true;
        }
        // Solo iniciar sesión si el navegador ya trae cookie (no crear sesiones para monitores)
        if (!isset($_COOKIE[(string) config('session.name')])) {
            return false;
        }
        return Auth::check() && Auth::role() === 'admin';
    }
}
