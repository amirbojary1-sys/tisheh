<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;

class TrustProxies extends \Illuminate\Http\Middleware\TrustProxies
{
    public function handle(Request $request, \Closure $next)
    {
        $value = config('tisheh.trusted_proxies');
        $this->proxies = $value === ['*'] ? '*' : $value;

        return parent::handle($request, $next);
    }
}
