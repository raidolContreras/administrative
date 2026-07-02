<?php

declare(strict_types=1);

namespace Core;

interface Middleware
{
    /** Devuelve una Response para cortar la cadena, o null para continuar */
    public function handle(Request $request, ?string $param = null): ?Response;
}
