<?php

declare(strict_types=1);

use App\Controllers\PageController;

/** @var Core\Router $router */

$router->get('/duenos', [PageController::class, 'show'], ['auth'], [
    'view' => __DIR__ . '/../Views/owners.php',
]);
$router->get('/mascotas', [PageController::class, 'show'], ['auth'], [
    'view' => __DIR__ . '/../Views/pets.php',
    'script' => 'modules/vet/pets.js',
]);
$router->get('/citas', [PageController::class, 'show'], ['auth'], [
    'view' => __DIR__ . '/../Views/appointments.php',
    'script' => 'modules/vet/citas.js',
]);
$router->get('/vacunas', [\App\Controllers\PageController::class, 'show'], ['auth'], ['view' => __DIR__ . '/../Views/vacunas.php']);
