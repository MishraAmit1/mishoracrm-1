<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\LoyaltyService;
use Illuminate\Console\Command;

class ExpireLoyaltyPoints extends Command
{
    protected $signature = 'loyalty:expire-points';

    protected $description = 'Expire loyalty points whose earn lot is past its expiry date (per-tenant rule)';

    public function handle(LoyaltyService $loyalty): int
    {
        $tenants = Tenant::query()->get()
            ->filter(fn (Tenant $t) => $t->hasModuleEnabled('loyalty'));

        $total = 0;

        foreach ($tenants as $tenant) {
            $expired = $loyalty->expireDuePoints($tenant->id);
            $total  += $expired;

            if ($expired > 0) {
                $this->line("  {$tenant->name}: {$expired} lot(s) expired");
            }
        }

        $this->info("Expired {$total} loyalty lot(s) across {$tenants->count()} tenant(s).");

        return self::SUCCESS;
    }
}
