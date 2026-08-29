<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\Tenant;
use App\Models\Vendor;
use App\Models\VendorBill;

class VendorBillService
{
    // ── Create — totals calc + collision-safe number generation ────
    public static function store(array $data, int $tenantId, int $userId): VendorBill
    {
        $items  = static::normalizeItems($data['items'] ?? []);
        $totals = VendorBill::calculateTotals($items, $data['discount'] ?? 0, $data['tax_percent'] ?? 0);
        $totals += static::gstColumns($tenantId, $data['vendor_id'] ?? null, (float) $totals['tax_amount']);

        return retry(3, function () use ($data, $items, $totals, $tenantId, $userId) {
            return VendorBill::create(array_merge($data, $totals, [
                'items'       => $items,
                'tenant_id'   => $tenantId,
                'number'      => VendorBill::generateNumber($tenantId),
                'created_by'  => $userId,
                'amount_paid' => 0,
                'status'      => 'unpaid',
            ]));
        }, 50, fn ($e) => static::isNumberCollision($e));
    }

    // ── Update — only reachable while nothing has been paid yet
    // (VendorBill::isEditable, enforced by controller). ──────────────
    public static function update(VendorBill $bill, array $data): VendorBill
    {
        $items  = static::normalizeItems($data['items'] ?? []);
        $totals = VendorBill::calculateTotals($items, $data['discount'] ?? 0, $data['tax_percent'] ?? 0);
        $totals += static::gstColumns(
            $bill->tenant_id,
            $data['vendor_id'] ?? $bill->vendor_id,
            (float) $totals['tax_amount']
        );

        $bill->update(array_merge($data, $totals, ['items' => $items]));

        return $bill;
    }

    // Vendor is the supplier, the tenant's company is the recipient.
    private static function gstColumns(int $tenantId, $vendorId, float $taxAmount): array
    {
        $vendor = $vendorId ? Vendor::withoutGlobalScopes()->find($vendorId) : null;
        $tenant = Tenant::find($tenantId);

        return \App\Services\GstService::documentColumns(
            $taxAmount,
            \App\Services\GstService::partyState($vendor),
            $tenant?->companyState(),
        );
    }

    // ── Record one or more payments against a bill, re-derive status ──
    public static function recordPayments(VendorBill $bill, array $rows, int $userId): VendorBill
    {
        foreach ($rows as $row) {
            $bill->payments()->create([
                'tenant_id'   => $bill->tenant_id,
                'amount'      => $row['amount'],
                'method'      => $row['method'],
                'paid_at'     => $row['paid_at'],
                'reference'   => $row['reference'] ?? null,
                'note'        => $row['note'] ?? null,
                'recorded_by' => $userId,
            ]);
        }

        $paid = (float) $bill->payments()->sum('amount');
        $bill->amount_paid = round($paid, 2);
        $bill->status      = $bill->deriveStatusFromPayments();
        $bill->save();

        return $bill;
    }

    // ── Cancel — keeps history, drops it out of AP totals ────────────
    public static function cancel(VendorBill $bill): VendorBill
    {
        $bill->update(['status' => 'cancelled']);

        return $bill;
    }

    // ── Pre-fill a bill from a Purchase Order's lines ────────────────
    public static function draftFromPurchaseOrder(PurchaseOrder $po): array
    {
        $items = collect($po->items ?? [])->map(fn ($item) => [
            'product_id'  => $item['product_id'] ?? null,
            'name'        => $item['name'] ?? '',
            'description' => $item['description'] ?? null,
            'quantity'    => (float) ($item['quantity'] ?? 0),
            'rate'        => (float) ($item['rate'] ?? 0),
            'tax_percent' => (float) ($item['tax_percent'] ?? 0),
            'amount'      => round((float) ($item['quantity'] ?? 0) * (float) ($item['rate'] ?? 0), 2),
        ])->toArray();

        return [
            'vendor_id'         => $po->vendor_id,
            'purchase_order_id' => $po->id,
            'items'             => $items,
            'discount'          => (float) $po->discount,
        ];
    }

    private static function normalizeItems(array $items): array
    {
        return collect($items)->map(fn ($item) => [
            'product_id'  => $item['product_id'] ?? null,
            'name'        => $item['name'] ?? '',
            'description' => $item['description'] ?? null,
            'quantity'    => (float) ($item['quantity'] ?? 0),
            'rate'        => (float) ($item['rate'] ?? 0),
            'tax_percent' => isset($item['tax_percent']) && $item['tax_percent'] !== '' ? (float) $item['tax_percent'] : 0,
            'amount'      => round((float) ($item['quantity'] ?? 0) * (float) ($item['rate'] ?? 0), 2),
        ])->toArray();
    }

    private static function isNumberCollision(\Throwable $e): bool
    {
        return $e instanceof \Illuminate\Database\QueryException
            && str_contains(strtolower($e->getMessage()), 'number');
    }
}
