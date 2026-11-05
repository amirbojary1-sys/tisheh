<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['initiator_read_at' => 'datetime', 'recipient_read_at' => 'datetime'];
    }

    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiator_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function scopeForUser($q, $id)
    {
        return $q->where(fn ($q) => $q->where('initiator_id', $id)->orWhere('recipient_id', $id));
    }

    public function contains(User $u): bool
    {
        return in_array($u->id, [$this->initiator_id, $this->recipient_id], true);
    }
}
