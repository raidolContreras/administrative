<?php

declare(strict_types=1);

namespace App\Middleware;

use Core\Csrf;
use Core\Middleware;
use Core\Request;
use Core\Response;

/**
 * Defensa CSRF en capas para métodos mutantes:
 * 1) token synchronizer en header X-CSRF-Token contra sesión (hash_equals)
 * 2) Sec-Fetch-Site debe ser same-origin (si el navegador lo envía)
 * 3) la cookie de sesión ya es SameSite=Lax
 */
final class CsrfMiddleware implements Middleware
{
    public function handle(Request $request, ?string $param = null): ?Response
    {
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return null;
        }

        $fetchSite = strtolower($request->header('Sec-Fetch-Site') ?? '');
        if ($fetchSite !== '' && !in_array($fetchSite, ['same-origin', 'none'], true)) {
            return Response::error(403, 'CSRF_ORIGIN', 'Petición rechazada por origen no permitido.');
        }

        if (!Csrf::validate($request->header('X-CSRF-Token'))) {
            return Response::error(403, 'CSRF_MISMATCH', 'Token de seguridad inválido o vencido. Recarga la página.');
        }
        return null;
    }
}
