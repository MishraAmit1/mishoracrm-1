<?php

// app/Models/Tenant.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'subdomain',
        'email',
        'phone',
        'logo',
        'timezone',
        'currency',
        'status',
        'settings'
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
