<?php

namespace App\Events;

use App\Models\Contact;
use App\Models\Lead;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeadConverted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Lead $lead,
        public Contact $contact,
    ) {}
}
