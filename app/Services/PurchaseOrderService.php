<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\Tenant;
use App\Models\Vendor;

class PurchaseOrderService
{
    // ── Create — totals calc + collision-safe number generation ────
    public static function store(array $data, int $tenantId, int $userId): PurchaseOrder
    {
        $items  = static::normalizeItems($data['items'] ?? []);
        $totals = PurchaseOrder::calculateTotals(
            $items,
            $data['discount'] ?? 0,
            $data['tax_percent'] ?? 0
        );
        $totals += static::gstColumns($tenantId, $data['vendor_id'] ?? null, (float) $totals['tax_amount']);

        return retry(3, function () use ($data, $items, $totals, $tenantId, $userId) {
            return PurchaseOrder::create(array_merge($data, $totals, [
                'items'      => $items,
                'tenant_id'  => $tenantId,
                'number'     => PurchaseOrder::generateNumber(),
                'created_by' => $userId,
                'status'     => $data['status'] ?? 'draft',
            ]));
        }, 50, fn ($e) => static::isNumberCollision($e));
    }

    // ── Update — recompute totals; preserve existing received_quantity
    // per row when items are re-submitted (matched by index/product_id,
    // clamped to the new quantity so a shrunk row can't keep a stale
    // over-received quantity). ─────────────────────────────────────
    public static function update(PurchaseOrder $purchaseOrder, array $data): PurchaseOrder
    {
        $existingItems = $purchaseOrder->items ?? [];
        $items         = static::normalizeItems($data['items'] ?? []);

        $items = collect($items)->map(function ($item, $index) use ($existingItems) {
            $existing = $existingItems[$index] ?? null;

            // Fall back to matching by product_id if index shifted.
            if (!$existing && !empty($item['product_id'])) {
                $existing = collect($existingItems)
                    ->firstWhere('product_id', $item['product_id']);
            }

            $previouslyReceived = (float) ($existing['received_quantity'] ?? 0);
            $newQty              = (float) ($item['quantity'] ?? 0);

            $item['received_quantity'] = min($previouslyReceived, $newQty);
            $item['rejected_quantity'] = (float) ($existing['rejected_quantity'] ?? 0);

            return $item;
        })->toArray();

        $totals = PurchaseOrder::calculateTotals(
            $items,
            $data['discount'] ?? 0,
            $data['tax_percent'] ?? 0
        );
        $totals += static::gstColumns(
            $purchaseOrder->tenant_id,
            $data['vendor_id'] ?? $purchaseOrder->vendor_id,
            (float) $totals['tax_amount']
        );

        $purchaseOrder->update(array_merge($data, $totals, ['items' => $items]));

        return $purchaseOrder;
    }

    // Resolve CGST/SGST/IGST split — vendor is the supplier, the tenant's
    // company is the recipient.
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

    // Receiving is handled by GoodsReceiptService (one GRN per delivery,
    // with quality inspection accept/reject).

    // Ensures every row carries a received_quantity key (defaults to 0
    // for brand-new rows created via store/update from the item form).
    private static function normalizeItems(array $items): array
    {
        return collect($items)->map(function ($item) {
            $item['received_quantity'] = (float) ($item['received_quantity'] ?? 0);

            return $item;
        })->toArray();
    }

    private static function isNumberCollision(\Throwable $e): bool
    {
        return $e instanceof \Illuminate\Database\QueryException
            && str_contains(strtolower($e->getMessage()), 'number');
    }
}
