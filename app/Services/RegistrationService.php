<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegistrationService
{
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = new User(collect($data)->only(['name', 'last_name', 'mobile', 'password'])->all());
            $user->role_id = Role::where('name', $data['role'])->sole()->id;
            $user->save();
            $user->profile()->create(collect($data)->only(['city', 'specialty', 'business_name'])->all());
            if ($data['role'] === 'seller') {
                $user->location()->create(['name' => $data['business_name'], 'city' => $data['city'], 'address' => $data['address']]);
            }
            app(AuditService::class)->record('user.registered', $user);

            return $user;
        });
    }
}
