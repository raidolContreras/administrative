<?php

declare(strict_types=1);

/*
 * Páginas del módulo base: cada ruta sirve un shell HTML sin datos
 * (app/Views/pages/<vista>.php dentro del layout indicado).
 */

use App\Controllers\PageController;

/** @var Core\Router $router */

$page = static function (string $pattern, string $view, array $middleware = [], string $layout = 'main') use ($router): void {
    $router->get($pattern, [PageController::class, 'show'], $middleware, ['view' => $view, 'layout' => $layout]);
};

$page('/login', 'login', ['guest'], 'auth');
$page('/instalar', 'install', [], 'auth');
$page('/', 'dashboard', ['auth']);
$page('/perfil', 'profile', ['auth']);
$page('/usuarios', 'users', ['auth', 'role:admin']);
$page('/auditoria', 'audit', ['auth', 'role:admin']);
$page('/configuracion', 'settings', ['auth', 'role:admin']);
