<?php

namespace App\Listeners;

use App\Events\LeadCreated;
use App\Services\LeadScoringService;

class RecalculateLeadScore
{
    public function handle(LeadCreated $event): void
    {
        LeadScoringService::recalculate($event->lead);
    }
}
