<?php

namespace App\Console\Commands;

use App\Models\ServiceSubscription;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\SubscriptionReminderService;
use Illuminate\Console\Command;

class SendSubscriptionExpiryAlerts extends Command
{
    protected $signature = 'subscriptions:remind-expiry {--days=30 : Outer window in days (safety ceiling — each tenant\'s own configured reminder-days setting is applied on top of this)}';

    protected $description = 'Notify tenant admins, and optionally the customer directly (Email/WhatsApp), about service subscriptions expiring soon';

    public function handle(NotificationService $notifications): int
    {
        $ceilingDays = (int) $this->option('days');

        $subscriptions = ServiceSubscription::withoutGlobalScopes()
            ->expiringSoon($ceilingDays)
            ->whereNull('expiry_notified_at')
            ->with(['contact', 'service', 'tenant'])
            ->get();

        $flagged = 0;

        foreach ($subscriptions as $subscription) {
            if (!$subscription->contact || !$subscription->service || !$subscription->tenant) {
                continue;
            }

            // Each tenant can configure its own "remind X days before expiry"
            // window; the --days option above is just a query-time ceiling.
            $daysLeft = $subscription->daysUntilExpiry();
            if ($daysLeft === null || $daysLeft > $subscription->tenant->subscriptionReminderDays()) {
                continue;
            }

            $admins = User::withoutGlobalScopes()
                ->where('tenant_id', $subscription->tenant_id)
                ->where('user_type', 'tenant_admin')
                ->where('is_active', true)
                ->get();

            foreach ($admins as $admin) {
                $notifications->send(
                    'subscription.expiring',
                    $admin,
                    [
                        'contact_name' => $subscription->contact->name,
                        'service_name' => $subscription->service->name,
                        'expiry_date'  => $subscription->expires_at?->format('d M Y'),
                    ],
                    null,
                    route('tenant.subscriptions.index', ['status' => 'expiring']),
                    $subscription
                );
            }

            // Customer-facing Email/WhatsApp — opt-in per tenant, honored
            // inside the service (null override = respect the toggle).
            SubscriptionReminderService::send($subscription);

            $subscription->update(['expiry_notified_at' => now()]);
            $flagged++;
        }

        $this->info("Flagged {$flagged} expiring subscription(s).");

        return self::SUCCESS;
    }
}
