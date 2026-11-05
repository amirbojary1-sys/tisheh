<?php

namespace App\Policies;

use App\Models\MaterialProduct;
use App\Models\User;

class MaterialProductPolicy
{
    public function view(?User $u, MaterialProduct $p): bool
    {
        return ($p->status === 'published' && $p->user->status === 'active') || ($u && ($u->id === $p->user_id || $u->hasPermission('admin.access')));
    }

    public function update(User $u, MaterialProduct $p): bool
    {
        return $u->id === $p->user_id && $u->hasPermission('products.create');
    }
}
