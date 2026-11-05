<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $attributes = ['status' => 'active'];

    protected $fillable = ['name', 'last_name', 'mobile', 'email', 'password'];

    protected $hidden = ['password', 'remember_token', 'email', 'mobile'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'mobile_verified_at' => 'datetime', 'email_verified_at' => 'datetime'];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    public function cart()
    {
        return $this->hasOne(ShoppingCart::class);
    }

    public function location()
    {
        return $this->hasOne(SellerLocation::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function skills()
    {
        return $this->belongsToMany(JobCategory::class, 'job_category_user');
    }

    public function portfolioEntries()
    {
        return $this->hasMany(PortfolioEntry::class);
    }

    public function hasRole(string $name): bool
    {
        return $this->role?->name === $name || ($this->relationLoaded('roles') ? $this->roles->contains('name', $name) : $this->roles()->where('name', $name)->exists());
    }

    public function hasPermission(string $name): bool
    {
        return $this->status === 'active' && (($this->role?->permissions()->where('name', $name)->exists() ?? false) || $this->roles()->whereHas('permissions', fn ($q) => $q->where('name', $name))->exists());
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->name.' '.$this->last_name);
    }
}
