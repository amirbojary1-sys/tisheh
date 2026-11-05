<?php

namespace App\Http\Controllers;

use App\Http\Requests\OtpRequest;
use App\Http\Requests\OtpVerifyRequest;
use App\Models\OtpChallenge;
use App\Services\OtpService;
use App\Support\DemoMode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class OtpController extends Controller
{
    public function form()
    {
        return view('auth.otp-mobile');
    }

    public function request(OtpRequest $r, OtpService $service)
    {
        DemoMode::ensureOtpEnabled();
        if ($old = $r->session()->get('otp_challenge_id')) {
            OtpChallenge::whereKey($old)->whereNull('consumed_at')->update(['expires_at' => now()]);
        }$token = Str::random(48);
        [$c,$code] = $service->issue($r->validated('mobile'), $token);
        $r->session()->put(['otp_challenge_id' => $c->id, 'otp_browser_token' => $token]);

        return redirect()->route('otp.code')->with('demo_otp_code', $code);
    }

    public function code(Request $r)
    {
        DemoMode::ensureOtpEnabled();
        $challenge = OtpChallenge::find($r->session()->get('otp_challenge_id'));
        if (! $challenge) {
            return redirect()->route('otp.form');
        }

        return view('auth.otp-code', compact('challenge'));
    }

    public function verify(OtpVerifyRequest $r, OtpService $service)
    {
        $u = $service->verify($r->session()->get('otp_challenge_id', ''), $r->session()->get('otp_browser_token', ''), $r->validated('code'));
        Auth::login($u);
        $r->session()->forget(['otp_challenge_id', 'otp_browser_token', 'demo_otp_code']);
        $r->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'ورود آزمایشی با کد انجام شد؛ پیامکی ارسال نشده و مالکیت موبایل تأیید نشده است.');
    }
}
