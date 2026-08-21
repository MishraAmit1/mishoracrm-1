<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionReminderLog extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'service_subscription_id',
        'contact_id',
        'channel',
        'status',
        'message',
        'error_message',
        'is_manual',
        'sent_at',
    ];

    protected $casts = [
        'is_manual' => 'boolean',
        'sent_at'   => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(ServiceSubscription::class, 'service_subscription_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
