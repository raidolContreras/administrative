<?php

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use Core\Model;

final class LoginAttempt extends Model
{
    protected static string $table = 'login_attempts';
    protected static array $fillable = ['identifier', 'ip', 'success', 'attempted_at'];
    protected static bool $timestamps = false;
    protected static bool $audited = false;

    public static function record(string $identifier, string $ip, bool $success): void
    {
        static::create([
            'identifier' => mb_substr($identifier, 0, 190),
            'ip' => mb_substr($ip, 0, 45),
            'success' => $success ? 1 : 0,
            'attempted_at' => now(),
        ]);
    }

    /** Rate limit: fallos recientes por correo O por IP dentro de la ventana */
    public static function tooManyRecent(string $identifier, string $ip, int $max, int $windowMinutes): bool
    {
        $since = date('Y-m-d H:i:s', time() - $windowMinutes * 60);
        $failures = (int) Database::scalar(
            'SELECT COUNT(*) FROM login_attempts
             WHERE success = 0 AND attempted_at >= ? AND (identifier = ? OR ip = ?)',
            [$since, $identifier, $ip]
        );
        return $failures >= $max;
    }

    /** Conteo por identificador exacto en la ventana (rate limit de flujos no-login, p. ej. "reset:email") */
    public static function recentCountByIdentifier(string $identifier, int $windowMinutes): int
    {
        return (int) Database::scalar(
            'SELECT COUNT(*) FROM login_attempts WHERE identifier = ? AND attempted_at >= ?',
            [mb_substr($identifier, 0, 190), date('Y-m-d H:i:s', time() - $windowMinutes * 60)]
        );
    }

    /** Limpieza ocasional (se llama al hacer login exitoso) */
    public static function gc(): void
    {
        Database::delete('login_attempts', 'attempted_at < ?', [date('Y-m-d H:i:s', time() - 7 * 86400)]);
    }
}
