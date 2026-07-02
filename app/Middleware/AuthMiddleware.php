<?php

declare(strict_types=1);

namespace App\Middleware;

use Core\Auth;
use Core\Middleware;
use Core\Request;
use Core\Response;
use Core\View;

final class AuthMiddleware implements Middleware
{
    public function handle(Request $request, ?string $param = null): ?Response
    {
        if (Auth::check()) {
            Auth::touch();
            return null;
        }
        if ($request->isApi()) {
            // Nunca redirect en API: fetch lo seguiría en silencio y recibiría HTML con status 200
            return Response::error(401, 'UNAUTHENTICATED', 'Debes iniciar sesión.');
        }
        $query = $_GET !== [] ? '?' . http_build_query($_GET) : '';
        $next = rawurlencode($request->path() . $query);
        return Response::redirect(View::url('/login') . '?next=' . $next);
    }
}
