<?php

declare(strict_types=1);

/*
 * Rutas de la API del módulo base. Convenciones:
 *  - lecturas:   ['auth', 'readonly']          (readonly libera el lock de sesión)
 *  - mutaciones: ['auth', 'csrf']              (+ 'role:admin' donde aplique)
 *  - $router->resource() registra el CRUD completo con esas convenciones
 */

use App\Controllers\Api\AttachmentController;
use App\Controllers\Api\AuditController;
use App\Controllers\Api\AuthController;
use App\Controllers\Api\BootstrapController;
use App\Controllers\Api\DashboardController;
use App\Controllers\Api\HealthController;
use App\Controllers\Api\InstallController;
use App\Controllers\Api\SettingController;
use App\Controllers\Api\UserController;

/** @var Core\Router $router */

// Públicos (sin sesión)
$router->get('/api/health', [HealthController::class, 'show']);
$router->get('/api/bootstrap', [BootstrapController::class, 'show']);
$router->get('/api/install/status', [InstallController::class, 'status']);
$router->post('/api/install', [InstallController::class, 'store'], ['csrf']);

// Autenticación
$router->post('/api/auth/login', [AuthController::class, 'login'], ['csrf']);
$router->post('/api/auth/logout', [AuthController::class, 'logout'], ['auth', 'csrf']);
$router->get('/api/auth/me', [AuthController::class, 'me'], ['auth', 'readonly']);
$router->post('/api/auth/password', [AuthController::class, 'changePassword'], ['auth', 'csrf']);
$router->post('/api/auth/forgot', [AuthController::class, 'forgot'], ['csrf']);
$router->post('/api/auth/reset', [AuthController::class, 'reset'], ['csrf']);

// Módulo base
$router->get('/api/dashboard', [DashboardController::class, 'show'], ['auth', 'readonly']);
$router->resource('/api/usuarios', UserController::class, ['role' => 'admin']);
$router->resource('/api/auditoria', AuditController::class, ['role' => 'admin', 'only' => ['index', 'show']]);
$router->get('/api/configuracion', [SettingController::class, 'index'], ['auth', 'role:admin', 'readonly']);
$router->put('/api/configuracion', [SettingController::class, 'update'], ['auth', 'role:admin', 'csrf']);
$router->post('/api/configuracion/logo', [SettingController::class, 'uploadLogo'], ['auth', 'role:admin', 'csrf']);

// Archivos privados
$router->post('/api/archivos', [AttachmentController::class, 'store'], ['auth', 'csrf']);
$router->get('/api/archivos/{id}/descargar', [AttachmentController::class, 'download'], ['auth', 'readonly']);
$router->delete('/api/archivos/{id}', [AttachmentController::class, 'destroy'], ['auth', 'csrf']);
