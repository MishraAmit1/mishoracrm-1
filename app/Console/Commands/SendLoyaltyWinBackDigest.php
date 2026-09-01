<?php

namespace App\Console\Commands;

use App\Models\Contact;
use App\Models\Tenant;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendLoyaltyWinBackDigest extends Command
{
    protected $signature = 'loyalty:win-back-digest';

    protected $description = 'Alert tenant admins about loyalty customers who have gone quiet (opt-in per tenant)';

    public function handle(NotificationService $notifications): int
    {
        $tenants = Tenant::query()->get()
            ->filter(fn (Tenant $t) => $t->hasModuleEnabled('loyalty')
                && ($t->loyaltySettings()['winback_digest'] ?? false));

        $sent = 0;

        foreach ($tenants as $tenant) {
            $days   = (int) $tenant->loyaltySettings()['inactive_days'];
            $cutoff = now()->subDays($days);

            $count = Contact::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('loyalty_lifetime_points', '>', 0)
                ->whereHas('invoices', fn ($q) => $q->where('status', 'paid'))
                ->whereDoesntHave('invoices', fn ($q) => $q->where('status', 'paid')->where('paid_at', '>=', $cutoff))
                ->count();

            if ($count === 0) {
                continue;
            }

            $admins = User::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('user_type', 'tenant_admin')
                ->where('is_active', true)
                ->get();

            foreach ($admins as $admin) {
                $notifications->send(
                    'loyalty.winback',
                    $admin,
                    ['count' => $count, 'days' => $days],
                    null,
                    route('tenant.loyalty.win-back'),
                );
                $sent++;
            }
        }

        $this->info("Sent {$sent} win-back digest notification(s).");

        return self::SUCCESS;
    }
}
