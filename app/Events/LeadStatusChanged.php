<?php

namespace App\Events;

use App\Models\Lead;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeadStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Lead $lead,
        public ?string $oldStatus,
        public string $newStatus,
    ) {}
}
