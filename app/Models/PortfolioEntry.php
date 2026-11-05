<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortfolioEntry extends Model
{
    protected $guarded = [];

    protected $hidden = ['image_path'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
