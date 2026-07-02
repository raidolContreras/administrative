<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Menú dinámico: items del módulo base + manifests de módulos activos,
 * filtrado por rol. El frontend solo lo pinta (/api/bootstrap).
 */
final class Menu
{
    private const BASE = [
        ['label' => 'Inicio', 'icon' => 'home', 'href' => '/', 'role' => null, 'order' => 10],
        ['label' => 'Usuarios', 'icon' => 'users', 'href' => '/usuarios', 'role' => 'admin', 'order' => 80],
        ['label' => 'Auditoría', 'icon' => 'clipboard', 'href' => '/auditoria', 'role' => 'admin', 'order' => 85],
        ['label' => 'Configuración', 'icon' => 'cog', 'href' => '/configuracion', 'role' => 'admin', 'order' => 90],
    ];

    public static function forRole(?string $role): array
    {
        $items = self::BASE;
        foreach (Modules::active() as $manifest) {
            $items = array_merge($items, $manifest->menu());
        }

        $visible = array_filter($items, static function (array $item) use ($role): bool {
            $required = $item['role'] ?? null;
            return $required === null || $role === $required || $role === 'admin';
        });

        usort($visible, static fn (array $a, array $b): int => ($a['order'] ?? 50) <=> ($b['order'] ?? 50));

        return array_map(static fn (array $item): array => [
            'label' => $item['label'],
            'icon' => $item['icon'] ?? 'dot',
            'href' => $item['href'],
        ], array_values($visible));
    }
}
