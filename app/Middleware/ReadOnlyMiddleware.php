<?php

declare(strict_types=1);

namespace App\Middleware;

use Core\Middleware;
use Core\Request;
use Core\Response;
use Core\Session;

/**
 * Libera el lock del archivo de sesión en endpoints de solo lectura.
 * Sin esto, los fetch paralelos de una misma página se ejecutan en fila
 * (PHP bloquea el archivo de sesión durante todo el request).
 * Colocar SIEMPRE después de 'auth' en la cadena de middlewares.
 */
final class ReadOnlyMiddleware implements Middleware
{
    public function handle(Request $request, ?string $param = null): ?Response
    {
        Session::writeClose();
        return null;
    }
}
