<?php

namespace App\Http\Middleware;

use Closure;

class EnsurePermission
{
    public function handle($request, Closure $next, string $permission)
    {
        abort_unless($request->user()?->hasPermission($permission), 403);

        return $next($request);
    }
}
