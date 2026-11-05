<?php

namespace App\Providers;

use App\Contracts\OtpSender;
use App\Contracts\PaymentGateway;
use App\Models\JobRequest;
use App\Models\MaterialProduct;
use App\Policies\JobRequestPolicy;
use App\Policies\MaterialProductPolicy;
use App\Services\Integrations\DemoGateway;
use App\Services\Integrations\DemoOtpSender;
use App\Support\Format;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OtpSender::class, DemoOtpSender::class);
        $this->app->bind(PaymentGateway::class, DemoGateway::class);
    }

    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());
        Gate::policy(JobRequest::class, JobRequestPolicy::class);
        Gate::policy(MaterialProduct::class, MaterialProductPolicy::class);
        RateLimiter::for('login', fn ($r) => [Limit::perMinute(5)->by($r->ip()), Limit::perMinute(5)->by(hash('sha256', Format::digits($r->input('mobile', ''))))]);
        RateLimiter::for('otp-send', fn ($r) => [Limit::perMinute(3)->by($r->ip()), Limit::perMinute(1)->by(hash('sha256', Format::digits($r->input('mobile', ''))))]);
        RateLimiter::for('otp-verify', fn ($r) => Limit::perMinute(10)->by($r->ip()));
        RateLimiter::for('register', fn ($r) => Limit::perHour(15)->by($r->ip()));
        RateLimiter::for('password-change', fn ($r) => Limit::perMinute(5)->by($r->user()?->id ?: $r->ip()));
        RateLimiter::for('writes', fn ($r) => Limit::perMinute(20)->by($r->user()?->id ?: $r->ip()));
    }
}
