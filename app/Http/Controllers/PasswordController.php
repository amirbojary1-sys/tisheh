<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PasswordController extends Controller
{
    public function update(ChangePasswordRequest $r)
    {
        DB::transaction(function () use ($r) {
            $u = User::whereKey($r->user()->id)->lockForUpdate()->firstOrFail();
            if (! Hash::check($r->validated('current_password'), $u->password)) {
                throw ValidationException::withMessages(['current_password' => 'رمز فعلی صحیح نیست.']);
            }
            $u->password = $r->validated('password');
            $u->remember_token = null;
            $u->save();
            DB::table('sessions')->where('user_id', $u->id)->delete();
            app(AuditService::class)->record('password.changed', $u);
        });
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'رمز تغییر کرد و نشست‌ها باطل شدند؛ با رمز جدید وارد شوید.');
    }
}
