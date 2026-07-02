<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LoginAttempt;
use App\Models\PasswordReset;
use App\Models\Setting;
use App\Models\User;
use App\Support\Mailer;

/**
 * Orquesta la recuperación de contraseña. Es el único "Service" del módulo de
 * auth porque es el único flujo con varias piezas coordinadas (rate limit,
 * token, correo, actualización); el resto vive en Controller→Model, como
 * define la arquitectura (Services solo cuando hay lógica compuesta real).
 *
 * Decisiones de seguridad:
 *  - Anti-enumeración: request() jamás revela si el correo existe; la
 *    respuesta al usuario es idéntica en todos los casos.
 *  - Rate limit propio (N solicitudes por correo en la ventana) registrado en
 *    login_attempts con el prefijo "reset:" — mismo registro de accesos que el
 *    login, con success=1 para no contaminar el contador de fallos del login
 *    (que solo cuenta success=0).
 *  - El enlace lleva SOLO el token (64 hex); el correo se resuelve en servidor
 *    por hash. Un uso, un token vigente por correo, y expiración corta.
 */
final class PasswordResetService
{
    /** Solicita el enlace. SIEMPRE silencioso: el controlador responde genérico. */
    public static function request(string $email, string $ip): void
    {
        $window = (int) config('security.reset_window_minutes', 15);
        $max = (int) config('security.reset_max_requests', 3);
        if (LoginAttempt::recentCountByIdentifier('reset:' . $email, $window) >= $max) {
            return; // silencioso: mismo mensaje genérico, sin oráculo de rate limit
        }
        LoginAttempt::record('reset:' . $email, $ip, true); // log de acceso del flujo

        $user = User::findByEmailForAuth($email);
        if ($user === null || (int) $user['is_active'] !== 1) {
            return; // silencioso: anti-enumeración
        }

        $ttl = (int) config('security.reset_token_minutes', 60);
        $token = PasswordReset::issue($email, $ttl);

        $appName = (string) Setting::get('app_name', config('app.name'));
        $link = absolute_url('/restablecer?token=' . $token);

        Mailer::send(
            $email,
            'Restablecer contraseña — ' . $appName,
            "Hola:\n\n"
            . "Recibimos una solicitud para restablecer la contraseña de tu cuenta en {$appName}.\n\n"
            . "Abre este enlace para elegir una nueva contraseña (vence en {$ttl} minutos):\n\n"
            . $link . "\n\n"
            . "Si tú no lo solicitaste, ignora este correo: tu contraseña actual sigue siendo válida.\n"
        );
    }

    /** Restablece con un token vigente. Devuelve false si el token no sirve. */
    public static function reset(string $token, string $newPassword, string $ip): bool
    {
        PasswordReset::gc();

        $row = PasswordReset::findValid($token);
        if ($row === null) {
            return false;
        }

        $email = (string) $row['email'];
        $user = User::findByEmailForAuth($email);
        if ($user === null || (int) $user['is_active'] !== 1) {
            PasswordReset::consumeAllFor($email); // cuenta inutilizable → token también
            return false;
        }

        // El update pasa por el Model base: auditado (con password_hash oculto por scrub)
        User::update((int) $user['id'], [
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
        ]);
        PasswordReset::consumeAllFor($email); // un solo uso
        LoginAttempt::record('reset-ok:' . $email, $ip, true); // rastro forense del cambio

        return true;
    }
}
