<?php

declare(strict_types=1);

namespace Core;

final class Session
{
    private static bool $booted = false;

    /** Configura la sesión endurecida (no la inicia — se inicia bajo demanda) */
    public static function boot(): void
    {
        if (self::$booted || PHP_SAPI === 'cli') {
            return;
        }
        self::$booted = true;

        // save_path propio: en shared hosting el directorio global es compartido y su GC es ajeno
        $savePath = storage_path('sessions');
        if (is_dir($savePath) && is_writable($savePath)) {
            session_save_path($savePath);
            ini_set('session.gc_probability', '1');
            ini_set('session.gc_divisor', '100');
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.gc_maxlifetime', (string) (max(30, (int) config('session.idle_minutes')) * 60 + 3600));

        session_name((string) config('session.name'));
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => (Request::basePath() ?: '') . '/',
            'domain' => '',
            'secure' => self::secureFlag(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    public static function start(): void
    {
        if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /** Persiste y libera el lock del archivo de sesión (los fetch paralelos dejan de serializarse) */
    public static function writeClose(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    public static function regenerate(): void
    {
        self::start();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function destroy(): void
    {
        self::start();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }
        $_SESSION = [];
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'],
        ]);
        session_destroy();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    private static function secureFlag(): bool
    {
        $mode = (string) config('session.secure', 'auto');
        if ($mode === 'auto') {
            try {
                return Request::current()->isSecure();
            } catch (\Throwable) {
                return false;
            }
        }
        return filter_var($mode, FILTER_VALIDATE_BOOL);
    }
}
