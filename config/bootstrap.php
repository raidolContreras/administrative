<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
const CORE_VERSION = '1.0.0';

/*
 * Autoload: Composer si existe vendor/; si no, PSR-4 propio.
 * Permite operar en hostings compartidos sin SSH donde no se puede correr composer
 * (el template no tiene dependencias de runtime).
 */
$composerAutoload = BASE_PATH . '/vendor/autoload.php';
if (is_file($composerAutoload)) {
    require $composerAutoload;
} else {
    spl_autoload_register(static function (string $class): void {
        $map = ['Core\\' => '/core/', 'App\\' => '/app/', 'Modules\\' => '/modules/'];
        foreach ($map as $prefix => $dir) {
            if (str_starts_with($class, $prefix)) {
                $file = BASE_PATH . $dir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
                if (is_file($file)) {
                    require $file;
                }
                return;
            }
        }
    });
}

require_once BASE_PATH . '/core/helpers.php';

Core\Env::load(BASE_PATH . '/.env');

$GLOBALS['__config'] = require __DIR__ . '/config.php';

date_default_timezone_set(config('app.timezone'));
mb_internal_encoding('UTF-8');

Core\ErrorHandler::register();
Core\Session::boot();
