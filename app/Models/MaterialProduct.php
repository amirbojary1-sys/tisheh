<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialProduct extends Model
{
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(MaterialCategory::class, 'material_category_id');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function scopePublished($q)
    {
        return $q->where('status', 'published')->whereHas('user', fn ($q) => $q->where('status', 'active'));
    }
}
