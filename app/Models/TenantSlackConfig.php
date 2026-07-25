<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantSlackConfig extends Model
{
    protected $fillable = ['tenant_id', 'webhook_url', 'is_active', 'last_tested_at'];

    protected $casts = [
        'is_active'      => 'boolean',
        'last_tested_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
