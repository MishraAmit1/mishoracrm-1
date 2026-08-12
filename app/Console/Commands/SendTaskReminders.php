<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendTaskReminders extends Command
{
    protected $signature = 'tasks:remind';

    protected $description = 'Send due-date notifications for tasks';

    public function handle(NotificationService $notifications): int
    {
        $due = Task::query()
            ->dueForReminder()
            ->with('assignedTo')
            ->get();

        foreach ($due as $task) {
            if ($task->assignedTo) {
                $notifications->send(
                    'task.due',
                    $task->assignedTo,
                    ['title' => $task->title],
                    null,
                    route('tenant.tasks.show', $task->id),
                    $task
                );
            }

            $task->update(['due_notified_at' => now()]);
        }

        $this->info("Sent {$due->count()} task due reminders.");

        return self::SUCCESS;
    }
}
