<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    protected $guarded = [];

    protected $hidden = ['national_id', 'national_id_fingerprint', 'contact_phone', 'private_address', 'postal_code', 'identity_review_note'];

    protected function casts(): array
    {
        return ['national_id' => 'encrypted', 'contact_phone' => 'encrypted', 'private_address' => 'encrypted', 'postal_code' => 'encrypted', 'identity_review_note' => 'encrypted', 'identity_revision' => 'integer', 'directory_revision' => 'integer', 'experience_years' => 'integer', 'directory_enabled' => 'boolean', 'public_contact' => 'boolean', 'identity_submitted_at' => 'datetime', 'identity_reviewed_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
