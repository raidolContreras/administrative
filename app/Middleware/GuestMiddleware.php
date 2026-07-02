<?php

declare(strict_types=1);

namespace App\Middleware;

use Core\Auth;
use Core\Middleware;
use Core\Request;
use Core\Response;
use Core\View;

final class GuestMiddleware implements Middleware
{
    public function handle(Request $request, ?string $param = null): ?Response
    {
        return Auth::check() ? Response::redirect(View::url('/')) : null;
    }
}
