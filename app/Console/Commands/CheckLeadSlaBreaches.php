<?php

namespace App\Console\Commands;

use App\Events\LeadSlaBreached;
use App\Models\Lead;
use Illuminate\Console\Command;

class CheckLeadSlaBreaches extends Command
{
    protected $signature = 'leads:check-sla';

    protected $description = 'Flag "new" leads with no contact within the SLA window and notify their assignee';

    public function handle(): int
    {
        $hours = config('leads.sla_hours', 4);

        $breached = Lead::query()
            ->where('status', 'new')
            ->whereNotNull('assigned_to')
            ->whereNull('contacted_at')
            ->whereNull('sla_notified_at')
            ->where('created_at', '<=', now()->subHours($hours))
            ->with('assignedTo')
            ->get();

        foreach ($breached as $lead) {
            event(new LeadSlaBreached($lead));
            $lead->update(['sla_notified_at' => now()]);
        }

        $this->info("Flagged {$breached->count()} SLA-breached lead(s) (threshold: {$hours}h).");

        return self::SUCCESS;
    }
}
