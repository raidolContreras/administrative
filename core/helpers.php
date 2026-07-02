<?php

declare(strict_types=1);

/** Lee un valor de configuración con notación de punto: config('db.host') */
function config(string $key, mixed $default = null): mixed
{
    $value = $GLOBALS['__config'] ?? [];
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

function base_path(string $path = ''): string
{
    return BASE_PATH . ($path !== '' ? DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR) : '');
}

function storage_path(string $path = ''): string
{
    return base_path('storage' . ($path !== '' ? '/' . $path : ''));
}

/** Fecha/hora actual en formato MySQL, zona horaria de la app */
function now(): string
{
    return date('Y-m-d H:i:s');
}
