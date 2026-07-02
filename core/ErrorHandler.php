<?php

declare(strict_types=1);

namespace Core;

use Core\Exceptions\HttpException;
use Core\Exceptions\ValidationException;

final class ErrorHandler
{
    private const TITLES = [
        401 => 'Sesión requerida',
        403 => 'Acceso denegado',
        404 => 'Página no encontrada',
        405 => 'Método no permitido',
        429 => 'Demasiadas peticiones',
        500 => 'Error interno',
    ];

    public static function register(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');
        ini_set('error_log', storage_path('logs/php-error.log'));

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        if (PHP_SAPI === 'cli') {
            set_exception_handler(static function (\Throwable $e): void {
                fwrite(STDERR, 'ERROR: ' . $e->getMessage() . PHP_EOL);
                if (config('app.debug')) {
                    fwrite(STDERR, $e->getTraceAsString() . PHP_EOL);
                }
                exit(1);
            });
            return;
        }

        ob_start();

        set_exception_handler(static function (\Throwable $e): void {
            self::respond($e)->send();
        });

        register_shutdown_function(static function (): void {
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                self::respond(new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']))->send();
            }
        });
    }

    /** Convierte cualquier excepción en una Response con el contrato estándar (JSON o página de error) */
    public static function respond(\Throwable $e): Response
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        ob_start();

        if ($e instanceof ValidationException) {
            return Response::error(422, 'VALIDATION_ERROR', $e->getMessage(), $e->errors);
        }

        if ($e instanceof HttpException) {
            if ($e->status >= 500) {
                self::log($e);
            }
            return self::wantsJson()
                ? Response::error($e->status, $e->errorCode, $e->getMessage(), $e->details)
                : self::htmlError($e->status, $e->getMessage());
        }

        self::log($e);
        $details = null;
        if (config('app.debug')) {
            $details = [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
                'trace' => array_slice(explode("\n", $e->getTraceAsString()), 0, 12),
            ];
        }
        return self::wantsJson()
            ? Response::error(500, 'SERVER_ERROR', 'Error interno del servidor.', $details)
            : self::htmlError(500, config('app.debug') ? $e->getMessage() : '');
    }

    public static function log(\Throwable $e): void
    {
        try {
            $dir = storage_path('logs');
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            $context = PHP_SAPI === 'cli'
                ? 'cli'
                : (($_SERVER['REQUEST_METHOD'] ?? '?') . ' ' . ($_SERVER['REQUEST_URI'] ?? '?') . ' ip=' . ($_SERVER['REMOTE_ADDR'] ?? '?'));
            $line = sprintf(
                "[%s] %s: %s en %s:%d (%s)\n%s\n",
                date('Y-m-d H:i:s'),
                $e::class,
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $context,
                $e->getTraceAsString()
            );
            @file_put_contents($dir . '/app-' . date('Y-m-d') . '.log', $line, FILE_APPEND | LOCK_EX);
        } catch (\Throwable) {
            // el log nunca debe tirar la respuesta
        }
    }

    private static function wantsJson(): bool
    {
        try {
            return Request::current()->wantsJson();
        } catch (\Throwable) {
            return true;
        }
    }

    private static function htmlError(int $status, string $detail = ''): Response
    {
        $title = self::TITLES[$status] ?? 'Error';
        try {
            return Response::html(View::renderError($status, $title, $detail), $status);
        } catch (\Throwable) {
            $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
            return Response::html(
                "<!doctype html><html lang=\"es\"><head><meta charset=\"utf-8\"><title>{$status}</title></head>"
                . "<body style=\"font-family:sans-serif;text-align:center;padding:4rem\">"
                . "<h1>{$status}</h1><p>{$safeTitle}</p></body></html>",
                $status
            );
        }
    }
}
