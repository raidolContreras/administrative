<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Registro de módulos verticales activos (config/modules.php).
 * Único punto donde se descubren manifests: core/ jamás conoce los módulos.
 */
final class Modules
{
    /** @var ModuleManifest[]|null */
    private static ?array $cache = null;

    /** @return string[] */
    public static function activeNames(): array
    {
        $file = base_path('config/modules.php');
        $names = is_file($file) ? require $file : [];
        return array_values(array_filter(array_map('strval', (array) $names)));
    }

    /** @return ModuleManifest[] */
    public static function active(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }
        $manifests = [];
        foreach (self::activeNames() as $name) {
            $file = base_path('modules/' . self::studly($name) . '/module.php');
            if (is_file($file)) {
                $definition = require $file;
                if (is_array($definition)) {
                    $manifests[] = new ModuleManifest($name, $definition);
                }
            }
        }
        return self::$cache = $manifests;
    }

    /** Fuentes de migraciones en orden: núcleo primero, luego módulos activos */
    public static function migrationSources(): array
    {
        $sources = ['core' => base_path('database/migrations')];
        foreach (self::active() as $manifest) {
            $sources[$manifest->key] = $manifest->path('migrations');
        }
        return $sources;
    }

    public static function studly(string $name): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $name)));
    }
}
