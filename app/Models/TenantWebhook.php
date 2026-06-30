<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantWebhook extends Model
{
    protected $fillable = ['tenant_id', 'event', 'webhook_url', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public static function events(): array
    {
        return [
            'lead.created'        => 'Lead Created',
            'lead.status_changed' => 'Lead Status Changed',
            'deal.won'            => 'Deal Won',
            'deal.lost'           => 'Deal Lost',
            'invoice.created'     => 'Invoice Created',
            'invoice.paid'        => 'Invoice Paid',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
