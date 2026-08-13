<?php

namespace App\Events;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeadAssigned
{
    use Dispatchable, SerializesModels;

    // $assignedBy is null when the assignment was made by the system
    // (e.g. auto-assignment) rather than a specific user action.
    public function __construct(
        public Lead $lead,
        public ?User $assignedBy = null,
    ) {}
}
