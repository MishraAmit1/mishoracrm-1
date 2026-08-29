<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class CheckPlatformSubscriptions extends Command
{
    protected $signature = 'subscriptions:check-platform {--reminder-days=7 : Warn tenants this many days before their plan expires}';

    protected $description = 'Expire lapsed workspace (platform) subscriptions and warn tenant admins about upcoming expiry';

    public function handle(NotificationService $notifications): int
    {
        $now          = now();
        $reminderDays = max(1, (int) $this->option('reminder-days'));
        $windowEnd    = $now->copy()->addDays($reminderDays);

        $subscriptions = Subscription::query()
            ->whereIn('status', ['active', 'trial'])
            ->with(['plan', 'tenant'])
            ->get();

        $expired  = 0;
        $reminded = 0;

        foreach ($subscriptions as $sub) {
            if (!$sub->tenant) {
                continue;
            }

            // ── 1. Lapsed → mark expired (isExpired() already skips free plans) ──
            if ($sub->isExpired()) {
                $sub->update(['status' => 'expired']);
                $this->notifyAdmins($notifications, $sub, 'subscription.platform_expired', [
                    'plan_name' => $sub->plan?->name ?? 'CRM',
                ]);
                $expired++;
                continue;
            }

            // ── 2. Expiring soon → one reminder per term ──
            if ($sub->isFree() || $sub->renewal_reminder_sent_at !== null) {
                continue;
            }

            $deadline = $sub->status === 'trial' ? $sub->trial_ends_at : $sub->ends_at;

            if ($deadline && $deadline->between($now, $windowEnd)) {
                $this->notifyAdmins($notifications, $sub, 'subscription.platform_expiring', [
                    'plan_name'   => $sub->plan?->name ?? 'CRM',
                    'expiry_date' => $deadline->format('d M Y'),
                    'days_left'   => (string) max(0, (int) $now->diffInDays($deadline, false)),
                ]);
                $sub->update(['renewal_reminder_sent_at' => now()]);
                $reminded++;
            }
        }

        $this->info("Platform subscriptions — expired: {$expired}, reminded: {$reminded}.");

        return self::SUCCESS;
    }

    private function notifyAdmins(NotificationService $notifications, Subscription $sub, string $type, array $data): void
    {
        $admins = User::withoutGlobalScopes()
            ->where('tenant_id', $sub->tenant_id)
            ->where('user_type', 'tenant_admin')
            ->where('is_active', true)
            ->get();

        foreach ($admins as $admin) {
            $notifications->send(
                $type,
                $admin,
                $data,
                null,
                route('tenant.subscription.plans'),
                $sub
            );
        }
    }
}
