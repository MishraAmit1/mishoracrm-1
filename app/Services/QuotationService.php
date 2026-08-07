<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Quotation;

class QuotationService
{
    // ── Mark quotation accepted, auto-create its invoice, and sync linked deal to Won ──
    public static function accept(Quotation $quotation): ?Invoice
    {
        if ($quotation->status !== 'accepted') {
            $quotation->update(['status' => 'accepted']);
        }

        if ($quotation->invoice) {
            return null;
        }

        // Quotation lead ke against bani thi bina contact ke — lead ko
        // ab contact/customer mein convert karo taaki invoice usse link ho sake.
        if (!$quotation->contact_id && $quotation->lead_id) {
            $lead = $quotation->lead ?? Lead::find($quotation->lead_id);

            if ($lead) {
                $contact = $lead->convertToContact();
                $quotation->update(['contact_id' => $contact->id]);
            }
        }

        $invoice = Invoice::create([
            'tenant_id'    => $quotation->tenant_id,
            'contact_id'   => $quotation->contact_id,
            'quotation_id' => $quotation->id,
            'number'       => Invoice::generateNumber(),
            'date'         => now()->toDateString(),
            'due_date'     => now()->addDays(30)->toDateString(),
            'items'        => $quotation->items,
            'subtotal'     => $quotation->subtotal,
            'discount'     => $quotation->discount,
            'tax_percent'  => $quotation->tax_percent,
            'tax_amount'   => $quotation->tax_amount,
            'total'        => $quotation->total,
            'notes'        => $quotation->notes,
            'terms'        => $quotation->terms,
            'status'       => 'draft',
            'created_by'   => auth()->id(),
        ]);

        static::syncDealWon($quotation);

        return $invoice;
    }

    // ── Quotation accepted → linked deal automatically Won ─────────
    public static function syncDealWon(Quotation $quotation): void
    {
        if (!$quotation->deal_id) {
            return;
        }

        $deal = $quotation->deal ?? Deal::find($quotation->deal_id);

        if ($deal && $deal->stage !== 'won') {
            $deal->update([
                'stage'             => 'won',
                'probability'       => 100,
                'actual_close_date' => now()->toDateString(),
                'stage_changed_at'  => now(),
            ]);
        }
    }
}
