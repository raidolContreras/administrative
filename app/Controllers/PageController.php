<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Request;
use Core\Response;
use Core\View;

/**
 * Sirve shells HTML: layout + estructura de la página, CERO datos de negocio.
 * El navegador llena la página vía fetch a /api (Alpine + api.js).
 */
final class PageController
{
    public function show(Request $request): Response
    {
        $view = (string) $request->routeDefault('view');
        $layout = (string) ($request->routeDefault('layout') ?? 'main');
        return Response::html(View::render($view, $layout, [
            'pageScript' => $request->routeDefault('script'),
        ]));
    }
}
