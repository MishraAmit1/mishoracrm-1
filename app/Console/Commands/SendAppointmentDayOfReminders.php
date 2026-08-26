<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Services\AppointmentReminderService;
use Illuminate\Console\Command;

class SendAppointmentDayOfReminders extends Command
{
    protected $signature = 'appointments:remind-today';

    protected $description = 'Send customers a same-day reminder (Email/WhatsApp) for appointments happening later today';

    public function handle(): int
    {
        $appointments = Appointment::withoutGlobalScopes()
            ->active()
            ->whereNull('day_of_reminder_sent_at')
            ->whereBetween('starts_at', [now()->startOfDay(), now()->endOfDay()])
            ->with(['contact', 'service', 'tenant'])
            ->get();

        $sent = 0;

        foreach ($appointments as $appointment) {
            if (!$appointment->contact || !$appointment->service || !$appointment->tenant) {
                continue;
            }

            AppointmentReminderService::sendDayOf($appointment);
            $appointment->update(['day_of_reminder_sent_at' => now()]);
            $sent++;
        }

        $this->info("Sent {$sent} same-day appointment reminder(s).");

        return self::SUCCESS;
    }
}
