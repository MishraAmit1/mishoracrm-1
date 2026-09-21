<?php

namespace App\Console\Commands;

use App\Models\Customer;
use Illuminate\Console\Command;

class PruneCustomers extends Command
{
    protected $signature = 'customers:prune';

    protected $description = 'Soft-delete junk customer accounts (never logged in, 30+ days old, linked to no shop)';

    public function handle(): int
    {
        $count = 0;

        Customer::whereNull('last_login_at')
            ->where('created_at', '<', now()->subDays(30))
            ->whereDoesntHave('contacts', fn ($q) => $q->withoutGlobalScopes())
            ->get()
            ->each(function (Customer $customer) use (&$count) {
                $customer->delete();
                $count++;
            });

        $this->info("Pruned {$count} junk customer account(s).");

        return self::SUCCESS;
    }
}
