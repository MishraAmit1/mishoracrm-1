<?php

namespace App\Observers;

use App\Events\LeadCreated;
use App\Events\LeadStatusChanged;
use App\Models\Lead;

class LeadObserver
{
    public function created(Lead $lead): void
    {
        event(new LeadCreated($lead));
    }

    public function updated(Lead $lead): void
    {
        if ($lead->wasChanged('status')) {
            event(new LeadStatusChanged($lead, $lead->getOriginal('status'), $lead->status));
        }
    }
}
