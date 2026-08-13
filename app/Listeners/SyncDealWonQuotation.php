<?php

namespace App\Listeners;

use App\Events\DealWon;
use App\Services\QuotationService;

// Auto-accepts a deal's latest pending quotation when it's marked won.
// Moved out of the controller (where it only ran on the Web path) into an
// event listener so both Web and API mark-won paths get it automatically.
class SyncDealWonQuotation
{
    public function handle(DealWon $event): void
    {
        $deal = $event->deal;

        $quotation = $deal->quotations()
            ->whereNotIn('status', ['accepted', 'rejected'])
            ->latest()
            ->first();

        if ($quotation) {
            QuotationService::accept($quotation);
        }
    }
}
