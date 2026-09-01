<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\LoyaltyService;
use Illuminate\Console\Command;

class SendLoyaltyExpiryReminders extends Command
{
    protected $signature = 'loyalty:expiry-reminders';

    protected $description = 'Nudge customers whose loyalty points lapse within the tenant-configured window';

    public function handle(LoyaltyService $loyalty): int
    {
        $total = 0;

        Tenant::query()->get()
            ->filter(fn (Tenant $t) => $t->hasModuleEnabled('loyalty'))
            ->each(function (Tenant $t) use ($loyalty, &$total) {
                $total += $loyalty->sendExpiryReminders($t);
            });

        $this->info("Sent {$total} points-expiry reminder(s).");

        return self::SUCCESS;
    }
}
