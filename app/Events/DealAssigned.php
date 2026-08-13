<?php

namespace App\Events;

use App\Models\Deal;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DealAssigned
{
    use Dispatchable, SerializesModels;

    // $assignedBy is null when the assignment was made by the system
    // rather than a specific user action.
    public function __construct(
        public Deal $deal,
        public ?User $assignedBy = null,
    ) {}
}
