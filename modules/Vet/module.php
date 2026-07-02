<?php

declare(strict_types=1);

/*
 * Manifest del módulo Veterinaria.
 * Puntos de extensión: menú, widgets de dashboard (+ rutas/migraciones/seeds por convención:
 * routes/{api,web}.php, migrations/*.sql, seeds/*.php dentro de este directorio).
 */
return [
    'name' => 'Veterinaria',
    'version' => '1.0.0',
    'menu' => [
        ['label' => 'Dueños', 'icon' => 'user-group', 'href' => '/duenos', 'role' => null, 'order' => 20],
        ['label' => 'Mascotas', 'icon' => 'heart', 'href' => '/mascotas', 'role' => null, 'order' => 30],
        ['label' => 'Citas', 'icon' => 'calendar', 'href' => '/citas', 'role' => null, 'order' => 40],
        ['label' => 'Vacunas', 'icon' => 'box', 'href' => '/vacunas', 'role' => null, 'order' => 45],
    ],
    'widgets' => [
        [Modules\Vet\Support\DashboardWidgets::class, 'stats'],
    ],
];
