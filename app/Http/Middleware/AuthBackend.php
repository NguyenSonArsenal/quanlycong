<?php

namespace App\Http\Middleware;

use Closure;

class AuthBackend
{
    public function handle($request, Closure $next)
    {
        if ($request->routeIs(backendRouteName('auth.*'))) {
            return $next($request);
        };
        if (!isBeLogin()) {
            return redirect()->route(backendRouteName('auth.login'));
        }
        return $next($request);
    }
}
