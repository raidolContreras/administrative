<?php

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

/**
 * Tokens de recuperación de contraseña.
 *
 * Decisiones:
 *  - Se guarda SOLO el hash SHA-256 del token: si la BD se filtra, los enlaces
 *    de restablecimiento no sirven (mismo principio que password_hash).
 *  - Un único token vigente por correo: emitir uno nuevo invalida el anterior.
 *  - Un solo uso: al restablecer se eliminan todos los tokens del correo.
 *  - Sin auditoría ni timestamps del Model base (created_at manual, no hay updated_at).
 */
final class PasswordReset extends Model
{
    protected static string $table = 'password_resets';
    protected static array $fillable = ['email', 'token_hash', 'expires_at', 'created_at'];
    protected static bool $timestamps = false;
    protected static bool $audited = false;

    /** Emite un token nuevo (invalida los previos del correo) y devuelve el valor EN CLARO para el enlace */
    public static function issue(string $email, int $ttlMinutes): string
    {
        Database::delete('password_resets', 'email = ?', [$email]);

        $token = bin2hex(random_bytes(32)); // 64 hex chars, imposible de adivinar
        static::create([
            'email' => $email,
            'token_hash' => hash('sha256', $token),
            'expires_at' => date('Y-m-d H:i:s', time() + $ttlMinutes * 60),
            'created_at' => now(),
        ]);

        return $token;
    }

    /** Token vigente (por hash y no expirado) o null */
    public static function findValid(string $token): ?array
    {
        return Database::selectOne(
            'SELECT * FROM password_resets WHERE token_hash = ? AND expires_at >= ? LIMIT 1',
            [hash('sha256', $token), now()]
        );
    }

    /** Un solo uso: consume todos los tokens del correo */
    public static function consumeAllFor(string $email): void
    {
        Database::delete('password_resets', 'email = ?', [$email]);
    }

    /** Limpieza de tokens vencidos (se llama al validar) */
    public static function gc(): void
    {
        Database::delete('password_resets', 'expires_at < ?', [now()]);
    }
}
