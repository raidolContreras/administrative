<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\LoginAttempt;
use App\Models\User;
use App\Services\PasswordResetService;
use Core\Auth;
use Core\Csrf;
use Core\Exceptions\HttpException;
use Core\Request;
use Core\Response;
use Core\Validator;

final class AuthController
{
    public function login(Request $request): Response
    {
        $data = Validator::validate((array) $request->input(), [
            'email' => 'required|email|max:190',
            'password' => 'required|max:72',
        ]);

        $max = (int) config('security.login_max_attempts', 5);
        $window = (int) config('security.login_window_minutes', 15);
        if (LoginAttempt::tooManyRecent($data['email'], $request->ip(), $max, $window)) {
            throw HttpException::tooManyRequests(
                'Demasiados intentos fallidos. Espera unos minutos e intenta de nuevo.',
                $window * 60
            );
        }

        $user = User::findByEmailForAuth($data['email']);
        $valid = $user !== null
            && (int) $user['is_active'] === 1
            && password_verify($data['password'], (string) $user['password_hash']);

        if (!$valid) {
            LoginAttempt::record($data['email'], $request->ip(), false);
            // Mensaje idéntico exista o no el correo (no filtrar cuentas)
            return Response::error(401, 'INVALID_CREDENTIALS', 'Correo o contraseña incorrectos.');
        }

        LoginAttempt::record($data['email'], $request->ip(), true);
        LoginAttempt::gc();

        Auth::login((int) $user['id'], ['role' => $user['role'], 'name' => $user['name']]);
        User::touchLastLogin((int) $user['id']);

        return Response::json([
            'user' => User::scrub($user),
            'csrf' => Csrf::token(), // rotado por Auth::login
        ]);
    }

    public function logout(Request $request): Response
    {
        Auth::logout();
        return Response::json(null);
    }

    public function me(Request $request): Response
    {
        return Response::json(Auth::user());
    }

    /**
     * Solicitud de recuperación. Respuesta SIEMPRE genérica (200) exista o no
     * el correo — la anti-enumeración vive aquí; el trabajo real, en el Service.
     */
    public function forgot(Request $request): Response
    {
        $data = Validator::validate((array) $request->input(), [
            'email' => 'required|email|max:190',
        ]);

        PasswordResetService::request($data['email'], $request->ip());

        return Response::json([
            'message' => 'Si el correo está registrado, te enviamos un enlace para restablecer la contraseña.',
        ]);
    }

    /** Restablecimiento con token (un solo uso, expira) */
    public function reset(Request $request): Response
    {
        $data = Validator::validate((array) $request->input(), [
            'token' => 'required|max:64',
            'password' => 'required|min:8|max:72',
        ]);

        if (!PasswordResetService::reset($data['token'], $data['password'], $request->ip())) {
            return Response::error(422, 'INVALID_TOKEN', 'El enlace no es válido o ya expiró. Solicita uno nuevo.');
        }

        return Response::json(null);
    }

    /** Cambio de contraseña del propio usuario (requiere la actual) */
    public function changePassword(Request $request): Response
    {
        $data = Validator::validate((array) $request->input(), [
            'current_password' => 'required|max:72',
            'new_password' => 'required|min:8|max:72',
        ]);

        $hash = User::passwordHash((int) Auth::id());
        if ($hash === null || !password_verify($data['current_password'], $hash)) {
            return Response::error(422, 'VALIDATION_ERROR', 'Los datos proporcionados no son válidos.', [
                'current_password' => ['La contraseña actual no es correcta.'],
            ]);
        }

        User::update((int) Auth::id(), [
            'password_hash' => password_hash($data['new_password'], PASSWORD_DEFAULT),
        ]);
        return Response::json(null);
    }
}
