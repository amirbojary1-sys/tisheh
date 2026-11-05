<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AccountRolesService
{
    public function update(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data) {
            $u = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            abort_if($u->hasRole('admin'), 403);
            $roles = Role::whereIn('name', $data['roles'])->get();
            abort_unless($roles->count() === count($data['roles']) && ! $roles->contains('name', 'admin'), 422);
            $u->roles()->sync($roles->pluck('id'));
            if (! $roles->contains('id', $u->role_id)) {
                $u->role_id = $roles->first()->id;
                $u->save();
            }
            if (in_array('seller', $data['roles'])) {
                $u->location()->updateOrCreate(['user_id' => $u->id], ['name' => $data['business_name'], 'address' => $data['address'], 'city' => $u->profile?->city ?? '']);
                $u->profile()->update(['business_name' => $data['business_name']]);
            }
            app(DirectoryService::class)->invalidate($u);
            app(AuditService::class)->record('account.roles_updated', $u, ['roles' => $data['roles']]);
        });
    }
}
