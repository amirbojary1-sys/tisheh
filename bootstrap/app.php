<?php

use App\Http\Middleware\EnsureActive;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\TrustProxies;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [EnsureActive::class, SecurityHeaders::class]);
        $middleware->alias(['permission' => EnsurePermission::class]);
        // Production: set TRUSTED_PROXIES to only your load balancer addresses.
        $middleware->replace(TrustProxies::class, App\Http\Middleware\TrustProxies::class);
    })->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['current_password', 'password', 'password_confirmation', 'national_id', 'contact_phone', 'private_address', 'postal_code']);
    })->create();
