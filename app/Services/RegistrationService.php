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
            abort_unless(in_array($data['role'], ['worker', 'employer', 'seller'], true) && count(array_diff($data['additional_roles'] ?? [], ['worker', 'employer', 'seller'])) === 0, 422);
            $user = new User(collect($data)->only(['name', 'last_name', 'mobile', 'password'])->all());
            $user->role_id = Role::where('name', $data['role'])->sole()->id;
            $user->save();
            $user->profile()->create(collect($data)->only(['city', 'specialty', 'business_name', 'activity_type'])->all());
            $roles = array_values(array_unique([$data['role'], ...($data['additional_roles'] ?? [])]));
            $user->roles()->sync(Role::whereIn('name', $roles)->pluck('id'));
            app(IdentityService::class)->save($user, $data);
            if (in_array('seller', $roles, true)) {
                $user->location()->create(['name' => $data['business_name'], 'city' => $data['city'], 'address' => $data['address']]);
            }
            app(AuditService::class)->record('user.registered', $user);

            return $user;
        });
    }
}
