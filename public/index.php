<?php

declare(strict_types=1);

/*
 * Front controller: TODA petición PHP entra por aquí.
 * Las URLs amigables llegan vía rewrite de .htaccess (Apache)
 * o vía este mismo archivo como router del servidor embebido (desarrollo).
 */

// Passthrough de archivos estáticos para `php -S` (solo desarrollo)
if (PHP_SAPI === 'cli-server') {
    $requested = __DIR__ . rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
    if ($requested !== __DIR__ . '/' && is_file($requested)) {
        return false;
    }
}

require dirname(__DIR__) . '/config/bootstrap.php';

$router = new Core\Router();
require dirname(__DIR__) . '/config/routes.php';

try {
    $response = $router->dispatch(Core\Request::current());
} catch (\Throwable $e) {
    $response = Core\ErrorHandler::respond($e);
}

$response->send();
