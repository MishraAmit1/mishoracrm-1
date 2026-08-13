<?php

namespace App\Observers;

use App\Events\DealAssigned;
use App\Events\DealCreated;
use App\Events\DealLost;
use App\Events\DealStageChanged;
use App\Events\DealWon;
use App\Models\Deal;

class DealObserver
{
    public function created(Deal $deal): void
    {
        event(new DealCreated($deal));
    }

    public function updated(Deal $deal): void
    {
        if ($deal->wasChanged('stage')) {
            $oldStage = $deal->getOriginal('stage');
            event(new DealStageChanged($deal, $oldStage, $deal->stage));

            if ($deal->stage === 'won') {
                event(new DealWon($deal));
            }

            if ($deal->stage === 'lost') {
                event(new DealLost($deal));
            }
        }

        if ($deal->wasChanged('assigned_to') && $deal->assigned_to) {
            event(new DealAssigned($deal, auth()->user()));
        }
    }
}
