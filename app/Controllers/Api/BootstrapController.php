<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\Setting;
use App\Models\User;
use App\Support\Menu;
use Core\Auth;
use Core\Csrf;
use Core\Request;
use Core\Response;
use Core\Session;

/**
 * Único fetch de arranque por página: usuario, menú por rol, token CSRF y settings públicos.
 * Accesible sin autenticar (entrega el token de la sesión anónima para proteger el propio login).
 */
final class BootstrapController
{
    public function show(Request $request): Response
    {
        Session::start();
        $authenticated = Auth::check();
        if ($authenticated) {
            Auth::touch();
        }
        $csrf = Csrf::token();
        $user = $authenticated ? Auth::user() : null;
        $menu = $authenticated ? Menu::forRole(Auth::role()) : [];

        // Degradar con gracia si la BD aún no está configurada (primera ejecución)
        $settings = [];
        $installed = false;
        try {
            $settings = Setting::publicAll();
            $installed = User::count() > 0;
        } catch (\Throwable) {
            // sin BD: el frontend redirige al instalador
        }

        Session::writeClose();

        return Response::json([
            'authenticated' => $authenticated,
            'user' => $user,
            'csrf' => $csrf,
            'menu' => $menu,
            'settings' => $settings,
            'installed' => $installed,
            'app' => [
                'name' => $settings['app_name'] ?? config('app.name'),
                'version' => (string) config('app.version'),
            ],
        ]);
    }
}
