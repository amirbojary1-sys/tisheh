<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemoPaymentAttempt extends Model
{
    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['is_demo' => 'boolean', 'expires_at' => 'datetime', 'finished_at' => 'datetime'];
    }

    public function order()
    {
        return $this->belongsTo(MaterialOrder::class, 'material_order_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
