<?php

namespace App\Services;

use App\Contracts\OtpSender;
use App\Models\OtpChallenge;
use App\Models\User;
use App\Support\DemoMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OtpService
{
    public function __construct(private OtpSender $sender) {}

    public function issue(string $mobile, string $browserToken): array
    {
        DemoMode::ensureOtpEnabled();
        $user = User::with('role')->where('mobile', $mobile)->where('status', 'active')->first();
        // Do not permit a displayed demo OTP to become a universal or administrator login bypass.
        $allowed = $user && ! $user->hasRole('admin') && in_array($mobile, config('integrations.otp_demo_mobiles'), true);
        $code = (string) random_int(1000, 9999);
        if ($allowed) {
            $this->sender->send($mobile, $code);
        }
        $challenge = OtpChallenge::create(['id' => (string) Str::uuid(), 'user_id' => $allowed ? $user->id : null, 'code_hash' => Hash::make($code), 'browser_token_hash' => hash('sha256', $browserToken), 'expires_at' => now()->addSeconds(config('integrations.otp_ttl_seconds')), 'is_demo' => true]);

        return [$challenge, $allowed ? $code : null];
    }

    public function verify(string $id, string $browserToken, string $code): User
    {
        DemoMode::ensureOtpEnabled();
        $result = DB::transaction(function () use ($id, $browserToken, $code) {
            $c = OtpChallenge::whereKey($id)->lockForUpdate()->first();
            if (! $c || ! $c->is_demo || ! hash_equals($c->browser_token_hash, hash('sha256', $browserToken))) {
                return ['error' => 'درخواست ورود معتبر نیست؛ کد جدید دریافت کنید.'];
            }
            if ($c->consumed_at || $c->expires_at->isPast() || $c->attempts >= config('integrations.otp_max_attempts')) {
                return ['error' => 'کد منقضی یا مسدود شده است؛ کد جدید دریافت کنید.'];
            }
            $c->increment('attempts');
            if (! Hash::check($code, $c->code_hash) || ! $c->user_id) {
                return ['error' => 'کد یا حساب آزمایشی معتبر نیست.'];
            }
            $user = User::with('role')->find($c->user_id);
            if (! $user || $user->status !== 'active' || $user->hasRole('admin') || ! in_array($user->mobile, config('integrations.otp_demo_mobiles'), true)) {
                return ['error' => 'کد یا حساب آزمایشی معتبر نیست.'];
            }
            $c->update(['consumed_at' => now()]);
            app(AuditService::class)->record('auth.demo_otp_consumed', $user, ['challenge_id' => $c->id, 'is_demo' => true]);

            // Demo proves no ownership of a real phone: never set mobile_verified_at here.
            return ['user' => $user];
        });
        if (isset($result['error'])) {
            throw ValidationException::withMessages(['code' => $result['error']]);
        }

        return $result['user'];
    }
}
