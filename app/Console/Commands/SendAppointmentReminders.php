<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Services\AppointmentReminderService;
use Illuminate\Console\Command;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:remind-upcoming {--hours=24 : How many hours before the appointment to send the reminder}';

    protected $description = 'Send customers a reminder (Email/WhatsApp) for appointments starting within the configured window';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');

        $appointments = Appointment::withoutGlobalScopes()
            ->active()
            ->whereNull('reminder_sent_at')
            ->where('starts_at', '>', now())
            ->where('starts_at', '<=', now()->addHours($hours))
            ->with(['contact', 'service', 'tenant'])
            ->get();

        $sent = 0;

        foreach ($appointments as $appointment) {
            if (!$appointment->contact || !$appointment->service || !$appointment->tenant) {
                continue;
            }

            AppointmentReminderService::send($appointment);
            $appointment->update(['reminder_sent_at' => now()]);
            $sent++;
        }

        $this->info("Sent {$sent} appointment reminder(s).");

        return self::SUCCESS;
    }
}
