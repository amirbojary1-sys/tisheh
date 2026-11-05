<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpChallenge extends Model
{
    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $hidden = ['code_hash', 'browser_token_hash'];

    protected function casts(): array
    {
        return ['is_demo' => 'boolean', 'expires_at' => 'datetime', 'consumed_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
