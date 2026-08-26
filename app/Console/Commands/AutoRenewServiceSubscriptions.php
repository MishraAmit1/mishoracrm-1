<?php

namespace App\Console\Commands;

use App\Models\ServiceSubscription;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\ServiceSubscriptionService;
use Illuminate\Console\Command;

class AutoRenewServiceSubscriptions extends Command
{
    protected $signature = 'subscriptions:auto-renew';

    protected $description = 'Auto-renew active subscriptions opted into auto-renew that have reached their expiry — extends the term and creates a draft renewal invoice';

    public function handle(NotificationService $notifications): int
    {
        $subscriptions = ServiceSubscription::withoutGlobalScopes()
            ->dueForAutoRenewal()
            ->with(['contact', 'service', 'tenant'])
            ->get();

        $renewed = 0;

        foreach ($subscriptions as $subscription) {
            if (!$subscription->contact || !$subscription->tenant) {
                continue;
            }

            $invoice = ServiceSubscriptionService::renew($subscription);
            $renewed++;

            $admins = User::withoutGlobalScopes()
                ->where('tenant_id', $subscription->tenant_id)
                ->where('user_type', 'tenant_admin')
                ->where('is_active', true)
                ->get();

            foreach ($admins as $admin) {
                $notifications->send(
                    'subscription.auto_renewed',
                    $admin,
                    [
                        'contact_name' => $subscription->contact->name,
                        'service_name' => $subscription->service?->name ?? '—',
                        'expiry_date'  => $subscription->expires_at?->format('d M Y'),
                        'invoice'      => $invoice?->number ?? '—',
                    ],
                    null,
                    $invoice ? route('tenant.invoices.show', $invoice->id) : route('tenant.subscriptions.index'),
                    $subscription
                );
            }
        }

        $this->info("Auto-renewed {$renewed} subscription(s).");

        return self::SUCCESS;
    }
}
