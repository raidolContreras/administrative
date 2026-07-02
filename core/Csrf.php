<?php

declare(strict_types=1);

namespace Core;

/**
 * Token synchronizer por sesión. Se entrega al frontend vía JSON (/api/bootstrap y login),
 * nunca renderizado en HTML; el frontend lo envía en el header X-CSRF-Token.
 */
final class Csrf
{
    private const KEY = '_csrf';

    public static function token(): string
    {
        Session::start();
        $token = Session::get(self::KEY);
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::set(self::KEY, $token);
        }
        return $token;
    }

    public static function validate(?string $token): bool
    {
        Session::start();
        $stored = Session::get(self::KEY);
        return is_string($token) && $token !== ''
            && is_string($stored) && $stored !== ''
            && hash_equals($stored, $token);
    }

    public static function rotate(): string
    {
        Session::start();
        $token = bin2hex(random_bytes(32));
        Session::set(self::KEY, $token);
        return $token;
    }
}
