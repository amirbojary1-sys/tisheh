<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialOrder extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['reservation_expires_at' => 'datetime', 'quote_acceptance_required' => 'boolean', 'quote_accepted_at' => 'datetime'];
    }

    public function demoPayments()
    {
        return $this->hasMany(DemoPaymentAttempt::class);
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function items()
    {
        return $this->hasMany(MaterialOrderItem::class);
    }

    public function events()
    {
        return $this->hasMany(OrderEvent::class)->orderBy('id');
    }

    public function scopeForUser($q, $id)
    {
        return $q->where(fn ($q) => $q->where('buyer_id', $id)->orWhere('seller_id', $id));
    }

    public function visibleTo(User $u): bool
    {
        return $u->hasPermission('admin.access') || in_array($u->id, [$this->buyer_id, $this->seller_id], true);
    }
}
