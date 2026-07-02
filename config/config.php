<?php

declare(strict_types=1);

use Core\Env;

return [
    'app' => [
        'name' => Env::get('APP_NAME', 'Panel Administrativo'),
        'env' => Env::get('APP_ENV', 'production'),
        'debug' => Env::bool('APP_DEBUG', false),
        'timezone' => Env::get('APP_TIMEZONE', 'America/Mexico_City'),
        'version' => CORE_VERSION,
        'asset_version' => Env::get('ASSET_VERSION', CORE_VERSION),
    ],

    'db' => [
        'host' => Env::get('DB_HOST', '127.0.0.1'),
        'port' => Env::get('DB_PORT', '3306'),
        'database' => Env::get('DB_DATABASE', ''),
        'username' => Env::get('DB_USERNAME', ''),
        'password' => Env::get('DB_PASSWORD', '') ?? '',
    ],

    'session' => [
        'name' => Env::get('SESSION_NAME', 'adm_session'),
        'idle_minutes' => (int) Env::get('SESSION_IDLE_MINUTES', '120'),
        // auto | true | false
        'secure' => Env::get('SESSION_SECURE', 'auto'),
    ],

    'security' => [
        'login_max_attempts' => 5,
        'login_window_minutes' => 15,
    ],

    'uploads' => [
        'max_bytes' => 5 * 1024 * 1024,
        // extensión => mimes aceptados (verificados con finfo, no con el MIME del cliente)
        'allowed' => [
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'webp' => ['image/webp'],
            'pdf' => ['application/pdf'],
        ],
    ],
];
