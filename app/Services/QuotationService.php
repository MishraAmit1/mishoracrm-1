<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Quotation;
use App\Models\Tenant;

class QuotationService
{
    // GST split — the tenant's company is the supplier, the customer
    // (contact) is the recipient.
    private static function gstColumns(int $tenantId, $contactId, float $taxAmount): array
    {
        $contact = $contactId ? Contact::withoutGlobalScopes()->find($contactId) : null;
        $tenant  = Tenant::find($tenantId);

        return GstService::documentColumns(
            $taxAmount,
            $tenant?->companyState(),
            GstService::partyState($contact),
        );
    }

    // ── Create — totals calc + collision-safe number generation ────
    // number is a unique column generated from max(id)+1; two concurrent
    // submissions can race and compute the same number, so retry a few
    // times on a unique-constraint violation rather than 500ing.
    public static function store(array $data, int $tenantId, int $userId): Quotation
    {
        $totals = Quotation::calculateTotals(
            $data['items'],
            $data['discount'] ?? 0,
            $data['tax_percent'] ?? 18
        );
        $totals += static::gstColumns($tenantId, $data['contact_id'] ?? null, (float) $totals['tax_amount']);

        return retry(3, function () use ($data, $totals, $tenantId, $userId) {
            return Quotation::create(array_merge($data, $totals, [
                'tenant_id'  => $tenantId,
                'number'     => Quotation::generateNumber(),
                'created_by' => $userId,
                'status'     => $data['status'] ?? 'draft',
            ]));
        }, 50, fn ($e) => static::isNumberCollision($e));
    }

    // ── Update — recompute totals from submitted items ──────────────
    public static function update(Quotation $quotation, array $data): Quotation
    {
        $totals = Quotation::calculateTotals(
            $data['items'],
            $data['discount'] ?? 0,
            $data['tax_percent'] ?? 18
        );
        $totals += static::gstColumns(
            $quotation->tenant_id,
            $data['contact_id'] ?? $quotation->contact_id,
            (float) $totals['tax_amount']
        );

        $quotation->update(array_merge($data, $totals));

        return $quotation;
    }

    // ── Clone a quotation as a new draft version, linked to the root ──
    public static function createNewVersion(Quotation $quotation, int $userId): Quotation
    {
        $root = $quotation->parent_quotation_id
            ? ($quotation->parentQuotation ?? $quotation)
            : $quotation;

        $latestVersion = Quotation::withoutGlobalScopes()
            ->where('tenant_id', $quotation->tenant_id)
            ->where(fn ($q) => $q->where('id', $root->id)->orWhere('parent_quotation_id', $root->id))
            ->max('version') ?? 1;

        return retry(3, function () use ($quotation, $root, $latestVersion, $userId) {
            return Quotation::create([
                'tenant_id'           => $quotation->tenant_id,
                'contact_id'          => $quotation->contact_id,
                'lead_id'             => $quotation->lead_id,
                'deal_id'             => $quotation->deal_id,
                'number'              => Quotation::generateNumber(),
                'date'                => now()->toDateString(),
                'valid_until'         => $quotation->valid_until,
                'items'               => $quotation->items,
                'subtotal'            => $quotation->subtotal,
                'discount'            => $quotation->discount,
                'tax_percent'         => $quotation->tax_percent,
                'tax_amount'          => $quotation->tax_amount,
                'place_of_supply'     => $quotation->place_of_supply,
                'is_inter_state'      => $quotation->is_inter_state,
                'cgst_amount'         => $quotation->cgst_amount,
                'sgst_amount'         => $quotation->sgst_amount,
                'igst_amount'         => $quotation->igst_amount,
                'total'               => $quotation->total,
                'currency'            => $quotation->currency,
                'notes'               => $quotation->notes,
                'terms'               => $quotation->terms,
                'status'              => 'draft',
                'created_by'          => $userId,
                'parent_quotation_id' => $root->id,
                'version'             => $latestVersion + 1,
            ]);
        }, 50, fn ($e) => static::isNumberCollision($e));
    }

    private static function isNumberCollision(\Throwable $e): bool
    {
        return $e instanceof \Illuminate\Database\QueryException
            && str_contains(strtolower($e->getMessage()), 'number');
    }

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
            'number'       => Invoice::generateNumber($quotation->tenant_id),
            'date'         => now()->toDateString(),
            'due_date'     => now()->addDays(30)->toDateString(),
            'items'        => $quotation->items,
            'subtotal'     => $quotation->subtotal,
            'discount'     => $quotation->discount,
            'tax_percent'  => $quotation->tax_percent,
            'tax_amount'   => $quotation->tax_amount,
            'place_of_supply' => $quotation->place_of_supply,
            'is_inter_state'  => $quotation->is_inter_state,
            'cgst_amount'  => $quotation->cgst_amount,
            'sgst_amount'  => $quotation->sgst_amount,
            'igst_amount'  => $quotation->igst_amount,
            'total'        => $quotation->total,
            'currency'     => $quotation->currency,
            'notes'        => $quotation->notes,
            'terms'        => $quotation->terms,
            'status'       => 'draft',
            'created_by'   => auth()->id() ?? $quotation->created_by,
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
