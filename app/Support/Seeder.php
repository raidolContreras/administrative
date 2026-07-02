<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Seeds como archivos PHP que devuelven un callable.
 * Núcleo: database/seeds/<nombre>.php  |  Módulos: modules/<Modulo>/seeds/<nombre>.php (clave "modulo:nombre")
 */
final class Seeder
{
    /** @return array<string, string> nombre => archivo */
    public static function available(): array
    {
        $seeds = [];
        foreach (glob(base_path('database/seeds') . '/*.php') ?: [] as $file) {
            $seeds[pathinfo($file, PATHINFO_FILENAME)] = $file;
        }
        foreach (Modules::active() as $manifest) {
            foreach (glob($manifest->path('seeds') . '/*.php') ?: [] as $file) {
                $seeds[$manifest->key . ':' . pathinfo($file, PATHINFO_FILENAME)] = $file;
            }
        }
        return $seeds;
    }

    /** Ejecuta un seed por nombre, o todos si $name es null. Devuelve los ejecutados. */
    public static function run(?string $name = null): array
    {
        $available = self::available();
        $toRun = $name === null ? $available : array_intersect_key($available, [$name => true]);
        if ($name !== null && $toRun === []) {
            throw new \InvalidArgumentException("Seed desconocido: {$name}. Disponibles: " . implode(', ', array_keys($available)));
        }
        $executed = [];
        foreach ($toRun as $key => $file) {
            $callable = require $file;
            if (is_callable($callable)) {
                $callable();
                $executed[] = $key;
            }
        }
        return $executed;
    }
}
