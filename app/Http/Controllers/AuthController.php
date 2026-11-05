<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\RegistrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, RegistrationService $service)
    {
        $user = $service->register($request->validated());
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'حساب شما ساخته شد. شماره موبایل هنوز تأیید نشده است.');
    }

    public function login(LoginRequest $request)
    {
        $data = $request->validated();
        if (! Auth::attempt([...$data, 'status' => 'active'])) {
            return back()->withErrors(['mobile' => 'شماره موبایل یا رمز عبور نادرست است.'])->onlyInput('mobile');
        }$request->session()->regenerate();

        $destination = $request->user()->hasRole('admin') ? route('admin') : route('dashboard');

        return redirect()->intended($destination);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
