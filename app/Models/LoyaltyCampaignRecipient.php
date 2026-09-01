<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyCampaignRecipient extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'loyalty_campaign_id', 'contact_id', 'code',
        'sent_at', 'redeemed_count', 'redeemed_at', 'redeemed_value',
    ];

    protected $casts = [
        'sent_at'        => 'datetime',
        'redeemed_at'    => 'datetime',
        'redeemed_count' => 'integer',
        'redeemed_value' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(LoyaltyCampaign::class, 'loyalty_campaign_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
