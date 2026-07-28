<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendInvoicePaymentReminders extends Command
{
    protected $signature = 'invoices:remind-payments';

    protected $description = 'Send due-soon & overdue payment reminders to the staff who created each invoice';

    public function handle(NotificationService $notifications): int
    {
        $dueSoon = Invoice::query()
            ->dueForPaymentReminder()
            ->with('createdBy')
            ->get();

        foreach ($dueSoon as $invoice) {
            $this->notify($notifications, $invoice, 'invoice.payment_due');
            $invoice->update(['due_reminded_at' => now()]);
        }

        $overdue = Invoice::query()
            ->overdueForPaymentReminder()
            ->with('createdBy')
            ->get();

        foreach ($overdue as $invoice) {
            $this->notify($notifications, $invoice, 'invoice.overdue');
            $invoice->update(['overdue_reminded_at' => now()]);
        }

        $this->info("Sent {$dueSoon->count()} due-soon + {$overdue->count()} overdue invoice payment reminders.");

        return self::SUCCESS;
    }

    private function notify(NotificationService $notifications, Invoice $invoice, string $type): void
    {
        if (! $invoice->createdBy) {
            return;
        }

        $notifications->send(
            $type,
            $invoice->createdBy,
            [
                'number' => $invoice->number,
                'amount' => number_format($invoice->due_amount, 2),
                'date'   => $invoice->due_date?->format('d M Y'),
            ],
            null,
            route('tenant.invoices.show', $invoice->id),
            $invoice
        );
    }
}
