<?php

namespace App\Http\Middleware;

use Closure;

class EnsureActive
{
    public function handle($request, Closure $next)
    {
        if ($request->user() && $request->user()->status !== 'active') {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['mobile' => 'حساب شما مسدود است؛ با پشتیبانی تماس بگیرید.']);
        }

        return $next($request);
    }
}
