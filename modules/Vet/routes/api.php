<?php

declare(strict_types=1);

use Modules\Vet\Controllers\AppointmentController;
use Modules\Vet\Controllers\OwnerController;
use Modules\Vet\Controllers\PetController;

/** @var Core\Router $router */

// Operación diaria: cualquier usuario autenticado (admin y empleado)
$router->resource('/api/duenos', OwnerController::class);
$router->resource('/api/mascotas', PetController::class);
$router->resource('/api/citas', AppointmentController::class);
$router->resource('/api/vacunas', \Modules\Vet\Controllers\VaccineController::class);
