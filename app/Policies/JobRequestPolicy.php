<?php

namespace App\Policies;

use App\Models\JobRequest;
use App\Models\User;

class JobRequestPolicy
{
    public function view(?User $u, JobRequest $j): bool
    {
        return ($j->status === 'published' && $j->user->status === 'active') || ($u && ($u->id === $j->user_id || $u->hasPermission('admin.access')));
    }

    public function update(User $u, JobRequest $j): bool
    {
        return $u->id === $j->user_id && $u->hasPermission('jobs.create');
    }

    public function delete(User $u, JobRequest $j): bool
    {
        return $this->update($u, $j);
    }

    public function apply(User $u, JobRequest $j): bool
    {
        return $u->hasRole('worker') && $j->status === 'published' && $j->user->status === 'active' && $u->id !== $j->user_id;
    }
}
