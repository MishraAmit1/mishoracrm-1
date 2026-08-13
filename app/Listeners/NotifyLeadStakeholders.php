<?php

namespace App\Listeners;

use App\Events\LeadAssigned;
use App\Events\LeadConverted;
use App\Events\LeadSlaBreached;
use App\Events\LeadStatusChanged;
use App\Services\NotificationService;

// In-app notifications for lead lifecycle events, wiring the lead.* types
// already defined in config/notifications.php. Pattern mirrors
// TaskController::notifyAssignment() — skip notifying whoever triggered
// the change themselves.
class NotifyLeadStakeholders
{
    public function handleLeadAssigned(LeadAssigned $event): void
    {
        $lead = $event->lead;

        if (!$lead->assigned_to || $event->assignedBy?->id === $lead->assigned_to) {
            return;
        }

        NotificationService::notify(
            'lead.assigned',
            $lead->assignedTo,
            ['name' => $lead->name],
            $event->assignedBy,
            route('tenant.leads.show', $lead->id),
            $lead
        );
    }

    public function handleLeadStatusChanged(LeadStatusChanged $event): void
    {
        $lead = $event->lead;

        // The 'converted' transition gets its own richer notification
        // (with a link to the new contact) via handleLeadConverted below.
        if ($event->newStatus === 'converted') {
            return;
        }

        if (!$lead->assigned_to || auth()->id() === $lead->assigned_to) {
            return;
        }

        NotificationService::notify(
            'lead.status_changed',
            $lead->assignedTo,
            ['name' => $lead->name, 'status' => $event->newStatus],
            auth()->user(),
            route('tenant.leads.show', $lead->id),
            $lead
        );
    }

    public function handleLeadConverted(LeadConverted $event): void
    {
        $lead = $event->lead;

        if (!$lead->assigned_to || auth()->id() === $lead->assigned_to) {
            return;
        }

        NotificationService::notify(
            'lead.converted',
            $lead->assignedTo,
            ['name' => $lead->name],
            auth()->user(),
            route('tenant.contacts.show', $event->contact->id),
            $lead
        );
    }

    public function handleLeadSlaBreached(LeadSlaBreached $event): void
    {
        $lead = $event->lead;

        if (!$lead->assigned_to) {
            return;
        }

        NotificationService::notify(
            'lead.sla_breached',
            $lead->assignedTo,
            ['name' => $lead->name],
            null,
            route('tenant.leads.show', $lead->id),
            $lead
        );
    }
}
