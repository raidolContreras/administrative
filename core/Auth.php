<?php

declare(strict_types=1);

namespace Core;

/**
 * Mecánica de autenticación por sesión. Genérica: la verificación de credenciales
 * y el modelo de usuario viven en app/ (core no conoce el dominio).
 */
final class Auth
{
    /** @var callable|null fn(int $id): ?array */
    private static $userResolver = null;
    private static ?array $userCache = null;
    private static bool $userLoaded = false;

    public static function resolveUserUsing(callable $fn): void
    {
        self::$userResolver = $fn;
    }

    /** Inicia sesión autenticada: regenera el ID (anti session-fixation) y rota el token CSRF */
    public static function login(int $id, array $data = []): void
    {
        Session::start();
        Session::regenerate();
        Session::set('auth', ['id' => $id, 'data' => $data, 'la' => time()]);
        Csrf::rotate();
        self::$userLoaded = false;
    }

    public static function logout(): void
    {
        Session::destroy();
        self::$userLoaded = false;
    }

    public static function check(): bool
    {
        Session::start();
        $auth = Session::get('auth');
        if (!is_array($auth) || !isset($auth['id'])) {
            return false;
        }
        $idle = max(0, (int) config('session.idle_minutes')) * 60;
        if ($idle > 0 && (time() - (int) ($auth['la'] ?? 0)) > $idle) {
            self::logout();
            return false;
        }
        return true;
    }

    /** Refresca la marca de actividad (expiración por inactividad) */
    public static function touch(): void
    {
        $auth = Session::get('auth');
        if (is_array($auth)) {
            $auth['la'] = time();
            Session::set('auth', $auth);
        }
    }

    public static function id(): ?int
    {
        $auth = Session::get('auth');
        return is_array($auth) ? (int) $auth['id'] : null;
    }

    /** Datos ligeros guardados en sesión al hacer login (role, name) */
    public static function data(?string $key = null, mixed $default = null): mixed
    {
        $data = Session::get('auth')['data'] ?? [];
        return $key === null ? $data : ($data[$key] ?? $default);
    }

    public static function role(): ?string
    {
        $role = self::data('role');
        return is_string($role) ? $role : null;
    }

    /** Usuario completo vía resolver registrado (cacheado por petición) */
    public static function user(): ?array
    {
        if (!self::$userLoaded) {
            self::$userLoaded = true;
            $id = self::id();
            self::$userCache = ($id !== null && self::$userResolver !== null)
                ? (self::$userResolver)($id)
                : null;
        }
        return self::$userCache;
    }
}
