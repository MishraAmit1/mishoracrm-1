<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class CheckTicketSlaBreaches extends Command
{
    protected $signature = 'tickets:check-sla';

    protected $description = 'Flag open tickets with no staff reply yet past their priority\'s SLA window, and notify the assignee (or tenant admins)';

    public function handle(): int
    {
        $flagged = 0;

        foreach (Ticket::slaHours() as $priority => $hours) {
            $breached = Ticket::withoutGlobalScopes()
                ->where('priority', $priority)
                ->whereIn('status', ['open', 'in_progress'])
                ->whereNull('sla_notified_at')
                ->where('created_at', '<=', now()->subHours($hours))
                ->whereDoesntHave('replies', fn ($q) => $q->where('is_customer_reply', false))
                ->with('assignee')
                ->get();

            foreach ($breached as $ticket) {
                $recipients = $ticket->assigned_to && $ticket->assignee
                    ? collect([$ticket->assignee])
                    : User::withoutGlobalScopes()
                        ->where('tenant_id', $ticket->tenant_id)
                        ->where('user_type', 'tenant_admin')
                        ->where('is_active', true)
                        ->get();

                foreach ($recipients as $recipient) {
                    NotificationService::notify(
                        'ticket.sla_breach',
                        $recipient,
                        ['subject' => $ticket->subject, 'priority' => $ticket->priority],
                        null,
                        route('tenant.tickets.show', $ticket->id),
                        $ticket
                    );
                }

                $ticket->update(['sla_notified_at' => now()]);
                $flagged++;
            }
        }

        $this->info("Flagged {$flagged} SLA-breached ticket(s).");

        return self::SUCCESS;
    }
}
