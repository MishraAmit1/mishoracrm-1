<?php

// app/Models/Tenant.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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

    public function getWebhookToken(): string
    {
        $settings = $this->settings ?? [];

        if (empty($settings['webhook_token'])) {
            $token = 'crm_whk_' . Str::random(40);
            $settings['webhook_token'] = $token;
            $this->update(['settings' => $settings]);
        }

        return $settings['webhook_token'];
    }

    public function regenerateWebhookToken(): string
    {
        $settings = $this->settings ?? [];
        $token = 'crm_whk_' . Str::random(40);
        $settings['webhook_token'] = $token;
        $this->update(['settings' => $settings]);

        return $token;
    }

    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }
}
