<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use Illuminate\Console\Command;

class MarkNoShowAppointments extends Command
{
    protected $signature = 'appointments:mark-no-show {--hours=2 : Grace period in hours after the appointment ends before auto-marking it}';

    protected $description = 'Auto-mark appointments as no-show if staff never updated their status within the grace period after they ended';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');

        $count = Appointment::withoutGlobalScopes()
            ->whereIn('status', ['booked', 'confirmed'])
            ->where('ends_at', '<=', now()->subHours($hours))
            ->update(['status' => 'no_show']);

        $this->info("Marked {$count} appointment(s) as no-show.");

        return self::SUCCESS;
    }
}
