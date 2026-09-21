<?php

namespace App\Console\Commands;

use App\Services\LoyaltyService;
use Illuminate\Console\Command;

class ExpireLoyaltyStamps extends Command
{
    protected $signature = 'loyalty:expire-stamps';

    protected $description = 'Reset part-filled stamp cards that have been idle past the tenant\'s stamp expiry window';

    public function handle(LoyaltyService $loyalty): int
    {
        $reset = $loyalty->expireDueStamps();

        $this->info("Reset {$reset} idle stamp card(s).");

        return self::SUCCESS;
    }
}
