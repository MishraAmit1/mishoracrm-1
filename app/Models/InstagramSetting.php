<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class InstagramSetting extends Model
{
    protected $fillable = [
        'tenant_id',
        'app_id',
        'app_secret',
        'access_token',
        'instagram_account_id',
        'page_id',
        'webhook_verify_token',
        'is_connected',
        'token_expires_at',
    ];

    protected $casts = [
        'is_connected'      => 'boolean',
        'token_expires_at'  => 'datetime',
    ];

    protected $hidden = ['app_secret', 'access_token'];

    public static function forTenant(int $tenantId): self
    {
        return static::firstOrNew(['tenant_id' => $tenantId]);
    }

    public function getAccessTokenAttribute(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return $value;
        }
    }

    public function setAccessTokenAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['access_token'] = null;
            return;
        }

        $this->attributes['access_token'] = Crypt::encryptString($value);
    }

    public function getAppSecretAttribute(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return $value;
        }
    }

    public function setAppSecretAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['app_secret'] = null;
            return;
        }

        $this->attributes['app_secret'] = Crypt::encryptString($value);
    }
}
