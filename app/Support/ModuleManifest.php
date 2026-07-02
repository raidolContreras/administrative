<?php

declare(strict_types=1);

namespace App\Support;

use Core\Router;

/**
 * Manifest de un módulo vertical (modules/<Modulo>/module.php).
 * Puntos de extensión: rutas api, rutas web, menú, migraciones, seeds, widgets de dashboard.
 */
final class ModuleManifest
{
    public function __construct(
        public readonly string $key,
        private readonly array $def,
    ) {
    }

    public function name(): string
    {
        return (string) ($this->def['name'] ?? $this->key);
    }

    public function version(): string
    {
        return (string) ($this->def['version'] ?? '1.0.0');
    }

    /** @return array[] items: label, icon, href, role|null, order */
    public function menu(): array
    {
        return (array) ($this->def['menu'] ?? []);
    }

    /** @return callable[] cada uno devuelve stats para el dashboard común */
    public function widgets(): array
    {
        return (array) ($this->def['widgets'] ?? []);
    }

    public function path(string $sub = ''): string
    {
        return base_path('modules/' . Modules::studly($this->key) . ($sub !== '' ? '/' . $sub : ''));
    }

    public function loadRoutes(Router $router): void
    {
        foreach (['api', 'web'] as $type) {
            $file = $this->path("routes/{$type}.php");
            if (is_file($file)) {
                require $file; // el archivo usa $router
            }
        }
    }
}
