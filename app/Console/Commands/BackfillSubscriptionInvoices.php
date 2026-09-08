<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\SubscriptionInvoiceService;
use Illuminate\Console\Command;

// One-time (safe to re-run) backfill of tax-invoice numbers for
// subscription terms that were paid before invoicing existed. Numbers are
// assigned in chronological order so the running sequence stays sane.
// Does NOT deliver anything — use the superadmin "Resend" button for that.
class BackfillSubscriptionInvoices extends Command
{
    protected $signature = 'invoices:backfill-subscriptions {--dry-run : List what would be issued without writing}';

    protected $description = 'Assign tax-invoice numbers to historical paid subscriptions that do not have one yet';

    public function handle(SubscriptionInvoiceService $invoices): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $pending = Subscription::query()
            ->whereNull('invoice_number')
            ->where(function ($q) {
                $q->whereNotNull('razorpay_payment_id')
                  ->orWhere('total_amount', '>', 0)
                  ->orWhere('original_amount', '>', 0);
            })
            ->with('plan')
            ->orderByRaw('COALESCE(started_at, created_at) asc')
            ->get()
            ->filter->isInvoiceable()
            ->values();

        if ($pending->isEmpty()) {
            $this->info('Nothing to backfill — every paid subscription already has an invoice number.');
            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[dry-run] ' : '') . "Backfilling {$pending->count()} invoice(s)…");

        $done = 0;
        foreach ($pending as $sub) {
            $label = sprintf(
                '#%d  %s  %s  ₹%s',
                $sub->id,
                $sub->plan?->name ?? '—',
                optional($sub->started_at ?? $sub->created_at)->format('Y-m-d'),
                number_format((float) ($sub->total_amount ?: $sub->original_amount ?: 0), 2),
            );

            if ($dryRun) {
                $this->line("  would issue → {$label}");
                continue;
            }

            try {
                $sub = $invoices->issue($sub);
                $this->line("  {$sub->invoice_number}  ←  {$label}");
                $done++;
            } catch (\Throwable $e) {
                $this->error("  failed for #{$sub->id}: {$e->getMessage()}");
            }
        }

        $this->info($dryRun ? 'Dry run complete.' : "Done — {$done} invoice number(s) assigned.");

        return self::SUCCESS;
    }
}
