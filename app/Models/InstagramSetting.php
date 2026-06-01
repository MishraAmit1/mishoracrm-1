<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'n8n_webhook_url',
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
}
