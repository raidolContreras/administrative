<?php

declare(strict_types=1);

namespace Core;

use Core\Exceptions\HttpException;

/**
 * Compositor de shells HTML. Las vistas son SOLO estructura (layout + página):
 * jamás interpolan datos de negocio; los datos llegan al navegador vía fetch a /api.
 * Únicas variables permitidas: constantes de infraestructura (base path, versión de assets).
 */
final class View
{
    public static function render(string $view, string $layout = 'main', array $vars = []): string
    {
        $viewPath = self::resolve($view);
        if (!is_file($viewPath)) {
            throw HttpException::notFound('Página no encontrada.');
        }

        $vars['viewName'] = $vars['viewName'] ?? pathinfo($viewPath, PATHINFO_FILENAME);
        $content = self::capture($viewPath, $vars);

        if ($layout === '') {
            return $content;
        }
        $layoutPath = base_path("app/Views/layouts/{$layout}.php");
        if (!is_file($layoutPath)) {
            return $content;
        }
        $vars['content'] = $content;
        return self::capture($layoutPath, $vars);
    }

    public static function renderError(int $status, string $title, string $detail = ''): string
    {
        return self::render(base_path('app/Views/pages/error.php'), '', [
            'status' => $status,
            'title' => $title,
            'detail' => $detail,
        ]);
    }

    /** Incluye un partial estructural dentro de una vista/layout */
    public static function partial(string $name, array $vars = []): void
    {
        $path = base_path("app/Views/partials/{$name}.php");
        if (is_file($path)) {
            echo self::capture($path, $vars);
        }
    }

    /** URL absoluta-relativa de un asset con cache-busting */
    public static function asset(string $path): string
    {
        return self::base() . '/assets/' . ltrim($path, '/')
            . '?v=' . rawurlencode((string) config('app.asset_version'));
    }

    public static function url(string $path = '/'): string
    {
        $base = self::base();
        $path = '/' . ltrim($path, '/');
        return ($base . $path) ?: '/';
    }

    public static function base(): string
    {
        return Request::basePath();
    }

    private static function resolve(string $view): string
    {
        // Ruta absoluta (vistas de módulos) o nombre relativo a app/Views/pages
        if (str_contains($view, DIRECTORY_SEPARATOR) || str_contains($view, '/') || preg_match('#^[A-Za-z]:#', $view)) {
            return $view;
        }
        return base_path("app/Views/pages/{$view}.php");
    }

    private static function capture(string $__file, array $__vars): string
    {
        extract($__vars, EXTR_SKIP);
        ob_start();
        require $__file;
        return (string) ob_get_clean();
    }
}
