<?php

declare(strict_types=1);

namespace Core;

final class Request
{
    private static ?self $current = null;
    private static ?string $basePathCache = null;

    private ?string $pathCache = null;
    private ?array $jsonCache = null;
    private array $routeParams = [];
    private array $routeDefaults = [];

    public static function current(): self
    {
        return self::$current ??= new self();
    }

    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Base path de la instalación (soporta subdirectorio y el rewrite raíz→public de cPanel).
     * '/index.php' → ''  |  '/sistema/index.php' → '/sistema'  |  '/public/index.php' → ''
     */
    public static function basePath(): string
    {
        if (self::$basePathCache !== null) {
            return self::$basePathCache;
        }
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        // dirname() reintroduce '\' en Windows para la raíz — normalizar SIEMPRE después
        $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');
        if (str_ends_with($dir, '/public')) {
            $dir = substr($dir, 0, -strlen('/public'));
        }
        return self::$basePathCache = ($dir === '' || $dir === '.') ? '' : $dir;
    }

    /** Ruta normalizada relativa al base path, sin query string ni slash final */
    public function path(): string
    {
        if ($this->pathCache !== null) {
            return $this->pathCache;
        }
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $uri = rawurldecode($uri);
        $base = self::basePath();
        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }
        $uri = preg_replace('#/{2,}#', '/', '/' . ltrim($uri, '/'));
        if ($uri !== '/' && str_ends_with($uri, '/')) {
            $uri = rtrim($uri, '/');
        }
        return $this->pathCache = $uri;
    }

    public function isApi(): bool
    {
        return $this->path() === '/api' || str_starts_with($this->path(), '/api/');
    }

    public function wantsJson(): bool
    {
        return $this->isApi() || str_contains($this->header('Accept') ?? '', 'application/json');
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }

    /** Cuerpo de la petición: JSON (application/json) con fallback a $_POST */
    public function input(?string $key = null, mixed $default = null): mixed
    {
        if ($this->jsonCache === null) {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            if (str_contains($contentType, 'application/json')) {
                $raw = file_get_contents('php://input') ?: '';
                $decoded = json_decode($raw, true);
                $this->jsonCache = is_array($decoded) ? $decoded : [];
            } else {
                $this->jsonCache = $_POST;
            }
        }
        if ($key === null) {
            return $this->jsonCache;
        }
        return $this->jsonCache[$key] ?? $default;
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        $value = $_SERVER[$key] ?? null;
        if ($value === null && strcasecmp($name, 'Content-Type') === 0) {
            $value = $_SERVER['CONTENT_TYPE'] ?? null;
        }
        return is_string($value) ? $value : null;
    }

    public function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function userAgent(): string
    {
        return substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    }

    public function isSecure(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }
        if (($_SERVER['SERVER_PORT'] ?? null) == 443) {
            return true;
        }
        // Proxy/balanceador (común en hostings con SSL terminado antes de Apache)
        return strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }

    /** @return array{name:string,type:string,tmp_name:string,error:int,size:int}|null */
    public function file(string $key): ?array
    {
        $file = $_FILES[$key] ?? null;
        return (is_array($file) && !is_array($file['name'])) ? $file : null;
    }

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    public function setRouteDefaults(array $defaults): void
    {
        $this->routeDefaults = $defaults;
    }

    public function routeDefault(string $key, mixed $default = null): mixed
    {
        return $this->routeDefaults[$key] ?? $default;
    }
}
