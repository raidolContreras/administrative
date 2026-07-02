<?php

declare(strict_types=1);

namespace Core;

use Core\Exceptions\HttpException;

final class Router
{
    /** @var array<string, array<string, array>> rutas estáticas [método][path] */
    private array $static = [];
    /** @var array<string, array[]> rutas con parámetros [método][] */
    private array $dynamic = [];
    /** @var array<string, class-string<Middleware>> */
    private array $aliases = [];

    public function alias(string $name, string $class): void
    {
        $this->aliases[$name] = $class;
    }

    public function get(string $pattern, callable|array $handler, array $middleware = [], array $defaults = []): void
    {
        $this->add('GET', $pattern, $handler, $middleware, $defaults);
    }

    public function post(string $pattern, callable|array $handler, array $middleware = [], array $defaults = []): void
    {
        $this->add('POST', $pattern, $handler, $middleware, $defaults);
    }

    public function put(string $pattern, callable|array $handler, array $middleware = [], array $defaults = []): void
    {
        $this->add('PUT', $pattern, $handler, $middleware, $defaults);
    }

    public function patch(string $pattern, callable|array $handler, array $middleware = [], array $defaults = []): void
    {
        $this->add('PATCH', $pattern, $handler, $middleware, $defaults);
    }

    public function delete(string $pattern, callable|array $handler, array $middleware = [], array $defaults = []): void
    {
        $this->add('DELETE', $pattern, $handler, $middleware, $defaults);
    }

    /**
     * Registra el CRUD REST completo de un recurso con los middlewares estándar.
     * $opts: 'role' (exigido en todo), 'writeRole' (solo en mutaciones), 'only' => ['index','show','store','update','destroy']
     */
    public function resource(string $base, string $controller, array $opts = []): void
    {
        $only = $opts['only'] ?? ['index', 'show', 'store', 'update', 'destroy'];
        $role = isset($opts['role']) ? ['role:' . $opts['role']] : [];
        $writeRole = isset($opts['writeRole']) ? ['role:' . $opts['writeRole']] : $role;
        $read = array_merge(['auth'], $role, ['readonly']);
        $write = array_merge(['auth'], $writeRole, ['csrf']);

        if (in_array('index', $only, true)) {
            $this->get($base, [$controller, 'index'], $read);
        }
        if (in_array('show', $only, true)) {
            $this->get($base . '/{id}', [$controller, 'show'], $read);
        }
        if (in_array('store', $only, true)) {
            $this->post($base, [$controller, 'store'], $write);
        }
        if (in_array('update', $only, true)) {
            $this->put($base . '/{id}', [$controller, 'update'], $write);
            $this->patch($base . '/{id}', [$controller, 'update'], $write);
        }
        if (in_array('destroy', $only, true)) {
            $this->delete($base . '/{id}', [$controller, 'destroy'], $write);
        }
    }

    private function add(string $method, string $pattern, callable|array $handler, array $middleware, array $defaults): void
    {
        $route = ['handler' => $handler, 'middleware' => $middleware, 'defaults' => $defaults, 'pattern' => $pattern];
        if (!str_contains($pattern, '{')) {
            $this->static[$method][$pattern === '/' ? '/' : rtrim($pattern, '/')] = $route;
            return;
        }
        $route['regex'] = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
        $this->dynamic[$method][] = $route;
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method() === 'HEAD' ? 'GET' : $request->method();
        $path = $request->path();

        $route = $this->match($method, $path, $params);
        if ($route === null) {
            $allowed = $this->allowedMethods($path);
            if ($allowed !== []) {
                throw HttpException::methodNotAllowed($allowed);
            }
            throw HttpException::notFound(
                $request->isApi() ? 'Endpoint no encontrado.' : 'Página no encontrada.'
            );
        }

        $request->setRouteParams($params);
        $request->setRouteDefaults($route['defaults']);

        foreach ($route['middleware'] as $spec) {
            $response = $this->runMiddleware($spec, $request);
            if ($response !== null) {
                return $response;
            }
        }

        return $this->invoke($route['handler'], $request);
    }

    private function match(string $method, string $path, ?array &$params): ?array
    {
        $params = [];
        if (isset($this->static[$method][$path])) {
            return $this->static[$method][$path];
        }
        foreach ($this->dynamic[$method] ?? [] as $route) {
            if (preg_match($route['regex'], $path, $m)) {
                $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
                return $route;
            }
        }
        return null;
    }

    private function allowedMethods(string $path): array
    {
        $allowed = [];
        $methods = array_unique(array_merge(array_keys($this->static), array_keys($this->dynamic)));
        foreach ($methods as $method) {
            if ($this->match($method, $path, $ignore) !== null) {
                $allowed[] = $method;
            }
        }
        return $allowed;
    }

    private function runMiddleware(string $spec, Request $request): ?Response
    {
        $parts = explode(':', $spec, 2);
        $name = $parts[0];
        $param = $parts[1] ?? null;
        $class = $this->aliases[$name]
            ?? throw new \LogicException("Middleware no registrado: {$name}");
        /** @var Middleware $middleware */
        $middleware = new $class();
        return $middleware->handle($request, $param);
    }

    private function invoke(callable|array $handler, Request $request): Response
    {
        if (is_array($handler) && is_string($handler[0])) {
            [$class, $action] = $handler;
            $result = (new $class())->{$action}($request);
        } else {
            $result = $handler($request);
        }
        if ($result instanceof Response) {
            return $result;
        }
        if (is_array($result)) {
            return Response::json($result);
        }
        return Response::html((string) $result);
    }
}
