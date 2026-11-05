<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialOrderItem extends Model
{
    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(MaterialProduct::class, 'material_product_id');
    }
}
