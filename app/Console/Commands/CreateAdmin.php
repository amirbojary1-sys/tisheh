<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'tisheh:admin';

    protected $description = 'Create initial admin securely using interactive input';

    public function handle(): int
    {
        $data = ['name' => $this->ask('نام مدیر'), 'mobile' => $this->ask('موبایل 09xxxxxxxxx'), 'password' => $this->secret('رمز عبور (حداقل ۱۲ نویسه، حروف بزرگ، کوچک و عدد)')];
        $v = Validator::make($data, ['name' => 'required|string|max:60', 'mobile' => ['required', 'regex:/^09[0-9]{9}$/', 'unique:users,mobile'], 'password' => ['required', Password::min(12)->mixedCase()->numbers()]]);
        if ($v->fails()) {
            $this->error($v->errors()->toJson(JSON_UNESCAPED_UNICODE));

            return self::FAILURE;
        }
        DB::transaction(function () use ($data) {
            $u = new User($data);
            $u->role_id = Role::where('name', 'admin')->sole()->id;
            $u->save();
            $u->profile()->create(['city' => 'تهران']);
            app(AuditService::class)->record('admin.created', $u);
        });
        $this->info('مدیر ساخته شد. تأیید شماره موبایل انجام نشده است.');

        return self::SUCCESS;
    }
}
