<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailSetting extends Model
{
    protected $fillable = [
        'tenant_id',
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'smtp_username',
        'smtp_password',
        'from_address',
        'from_name',
        'is_connected',
        'last_tested_at',
    ];

    protected $casts = [
        'smtp_port'      => 'integer',
        'is_connected'   => 'boolean',
        'last_tested_at' => 'datetime',
    ];

    protected $hidden = ['smtp_password'];

    public static function forTenant(int $tenantId): self
    {
        return static::firstOrNew(['tenant_id' => $tenantId]);
    }
}
