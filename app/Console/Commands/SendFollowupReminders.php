<?php

namespace App\Console\Commands;

use App\Models\Followup;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendFollowupReminders extends Command
{
    protected $signature = 'followups:remind';

    protected $description = 'Send due & overdue notifications for scheduled follow-ups';

    public function handle(NotificationService $notifications): int
    {
        $due = Followup::query()
            ->dueForReminder()
            ->with(['lead', 'contact', 'assignedTo'])
            ->get();

        foreach ($due as $followup) {
            $this->notify($notifications, $followup, 'followup.due');
            $followup->update(['due_notified_at' => now()]);
        }

        $overdue = Followup::query()
            ->overdueForReminder()
            ->with(['lead', 'contact', 'assignedTo'])
            ->get();

        foreach ($overdue as $followup) {
            $this->notify($notifications, $followup, 'followup.overdue');
            $followup->update(['overdue_notified_at' => now()]);
        }

        $this->info("Sent {$due->count()} due + {$overdue->count()} overdue follow-up reminders.");

        return self::SUCCESS;
    }

    private function notify(NotificationService $notifications, Followup $followup, string $type): void
    {
        if (! $followup->assignedTo) {
            return;
        }

        $entityName = $followup->lead?->name
            ?? $followup->contact?->name
            ?? 'customer';

        $notifications->send(
            $type,
            $followup->assignedTo,
            ['name' => $entityName],
            null,
            route('tenant.followups.show', $followup),
            $followup
        );
    }
}
