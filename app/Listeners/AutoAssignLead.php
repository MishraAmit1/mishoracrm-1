<?php

namespace App\Listeners;

use App\Events\LeadAssigned;
use App\Events\LeadCreated;
use App\Services\LeadAutoAssignmentService;

class AutoAssignLead
{
    public function handle(LeadCreated $event): void
    {
        $lead = $event->lead;

        if ($lead->assigned_to) {
            return;
        }

        if (LeadAutoAssignmentService::assign($lead)) {
            event(new LeadAssigned($lead, null));
        }
    }
}
