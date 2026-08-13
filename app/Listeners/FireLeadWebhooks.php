<?php

namespace App\Listeners;

use App\Events\LeadCreated;
use App\Events\LeadStatusChanged;
use App\Services\WebhookService;

// Outbound tenant webhooks for lead lifecycle events — moved out of the
// controllers so every creation/status-change path (web, API, imports,
// lead-source integrations) fires consistently instead of only the ones
// a controller happened to remember to call WebhookService::fire() from.
class FireLeadWebhooks
{
    public function handleLeadCreated(LeadCreated $event): void
    {
        $lead = $event->lead;

        WebhookService::fire('lead.created', $lead->tenant_id, [
            'id'     => $lead->id,
            'name'   => $lead->name,
            'phone'  => $lead->phone,
            'email'  => $lead->email,
            'source' => $lead->source,
            'status' => $lead->status,
        ]);
    }

    public function handleLeadStatusChanged(LeadStatusChanged $event): void
    {
        $lead = $event->lead;

        WebhookService::fire('lead.status_changed', $lead->tenant_id, [
            'id'         => $lead->id,
            'name'       => $lead->name,
            'phone'      => $lead->phone,
            'old_status' => $event->oldStatus,
            'new_status' => $event->newStatus,
        ]);
    }
}
