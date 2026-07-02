<?php

declare(strict_types=1);

/*
 * Composition root: aquí (y solo aquí) se conecta core/ con app/ y los módulos.
 * core/ no conoce el dominio; este archivo le inyecta middlewares, resolver de
 * usuario y observador de auditoría, y carga las rutas del base + módulos activos.
 */

use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\ReadOnlyMiddleware;
use App\Middleware\RoleMiddleware;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\Modules;
use Core\Auth;
use Core\Model;

/** @var Core\Router $router */

$router->alias('auth', AuthMiddleware::class);
$router->alias('guest', GuestMiddleware::class);
$router->alias('role', RoleMiddleware::class);
$router->alias('csrf', CsrfMiddleware::class);
$router->alias('readonly', ReadOnlyMiddleware::class);

Auth::resolveUserUsing(static fn (int $id): ?array => User::find($id));
Model::observeUsing(AuditLog::record(...));

require __DIR__ . '/routes/api.php';
require __DIR__ . '/routes/web.php';

foreach (Modules::active() as $module) {
    $module->loadRoutes($router);
}
