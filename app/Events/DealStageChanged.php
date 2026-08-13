<?php

namespace App\Events;

use App\Models\Deal;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DealStageChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Deal $deal,
        public ?string $oldStage,
        public string $newStage,
    ) {}
}
