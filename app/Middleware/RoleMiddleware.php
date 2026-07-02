<?php

declare(strict_types=1);

namespace App\Middleware;

use Core\Auth;
use Core\Middleware;
use Core\Request;
use Core\Response;
use Core\View;

final class RoleMiddleware implements Middleware
{
    public function handle(Request $request, ?string $param = null): ?Response
    {
        if ($param === null || Auth::role() === $param) {
            return null;
        }
        if ($request->isApi()) {
            return Response::error(403, 'FORBIDDEN', 'No tienes permisos para esta acción.');
        }
        return Response::html(View::renderError(403, 'Acceso denegado'), 403);
    }
}
