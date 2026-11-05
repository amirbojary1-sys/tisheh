<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class ResetPassword extends Command
{
    protected $signature = 'tisheh:reset-password';

    protected $description = 'Operator-only interactive recovery until SMS integration';

    public function handle(): int
    {
        $mobile = $this->ask('موبایل حساب');
        $user = User::where('mobile', $mobile)->first();
        if (! $user) {
            $this->error('کاربر یافت نشد.');

            return self::FAILURE;
        }
        $password = $this->secret('رمز جدید (حداقل ۱۲ نویسه، حروف بزرگ، کوچک و عدد)');
        $v = Validator::make(['password' => $password], ['password' => ['required', Password::min(12)->mixedCase()->numbers()]]);
        if ($v->fails()) {
            $this->error($v->errors()->first());

            return self::FAILURE;
        }
        if (! $this->confirm('آیا مالکیت حساب را خارج از سامانه احراز کرده‌اید؟', false)) {
            return self::FAILURE;
        }
        DB::transaction(function () use ($user, $password) {
            $user->update(['password' => $password]);
            $user->remember_token = null;
            $user->save();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            app(AuditService::class)->record('password.reset_by_operator', $user);
        });
        $this->info('رمز تغییر کرد و نشست‌ها باطل شدند.');

        return self::SUCCESS;
    }
}
