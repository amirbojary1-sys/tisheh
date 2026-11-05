<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartCheckout extends Model
{
    protected $guarded = [];

    public function orders()
    {
        return $this->belongsToMany(MaterialOrder::class, 'cart_checkout_orders', 'cart_checkout_id', 'material_order_id');
    }
}
