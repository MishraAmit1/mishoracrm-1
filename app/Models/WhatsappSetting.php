<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappSetting extends Model
{
    protected $fillable = [
        'tenant_id',
        'phone_number_id',
        'waba_id',
        'display_phone_number',
        'verified_name',
        'access_token',
        'webhook_verify_token',
        'chatbot_enabled',
        'is_connected',
    ];

    protected $casts = [
        'chatbot_enabled' => 'boolean',
        'is_connected'    => 'boolean',
    ];

    protected $hidden = ['access_token'];

    public static function forTenant(int $tenantId): self
    {
        return static::firstOrNew(['tenant_id' => $tenantId]);
    }
}
