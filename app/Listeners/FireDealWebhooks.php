<?php

namespace App\Listeners;

use App\Events\DealLost;
use App\Events\DealWon;
use App\Services\WebhookService;

// Outbound tenant webhooks for deal lifecycle events — moved out of the
// controllers so both the Web and API paths fire consistently instead of
// only the ones a controller happened to remember to call
// WebhookService::fire() from. Only deal.won/deal.lost are wired, matching
// the subscribable event list in TenantWebhook::events().
class FireDealWebhooks
{
    public function handleDealWon(DealWon $event): void
    {
        $deal = $event->deal;

        WebhookService::fire('deal.won', $deal->tenant_id, [
            'id'            => $deal->id,
            'title'         => $deal->title,
            'value'         => $deal->value,
            'contact_name'  => $deal->contact?->name,
            'contact_phone' => $deal->contact?->phone,
            'close_date'    => $deal->actual_close_date,
        ]);
    }

    public function handleDealLost(DealLost $event): void
    {
        $deal = $event->deal;

        WebhookService::fire('deal.lost', $deal->tenant_id, [
            'id'          => $deal->id,
            'title'       => $deal->title,
            'value'       => $deal->value,
            'lost_reason' => $deal->lost_reason,
        ]);
    }
}
