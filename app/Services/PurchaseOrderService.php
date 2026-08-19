<?php

namespace App\Services;

use App\Models\PurchaseOrder;

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

            return $item;
        })->toArray();

        $totals = PurchaseOrder::calculateTotals(
            $items,
            $data['discount'] ?? 0,
            $data['tax_percent'] ?? 0
        );

        $purchaseOrder->update(array_merge($data, $totals, ['items' => $items]));

        return $purchaseOrder;
    }

    // ── Receive — record received qty per line (clamped to ordered
    // qty), re-derive status from the resulting item rows. Also bumps
    // raw-material stock for any row linked to a product_id, by the
    // delta (not the cumulative total) so repeated partial receives
    // don't double-count. ────────────────────────────────────────
    public static function receive(PurchaseOrder $purchaseOrder, array $receivedByRowIndex): PurchaseOrder
    {
        $items = collect($purchaseOrder->items ?? [])->map(function ($item, $index) use ($receivedByRowIndex) {
            if (array_key_exists($index, $receivedByRowIndex)) {
                $qty         = (float) ($item['quantity'] ?? 0);
                $oldReceived = (float) ($item['received_quantity'] ?? 0);
                $newReceived = max(0, min($qty, (float) $receivedByRowIndex[$index]));
                $delta       = $newReceived - $oldReceived;

                $item['received_quantity'] = $newReceived;

                if ($delta != 0 && !empty($item['product_id'])) {
                    StockService::applyReceivedDelta((int) $item['product_id'], $delta);
                }
            }

            return $item;
        })->toArray();

        $status = static::deriveReceivedStatus($items);

        $purchaseOrder->update([
            'items'  => $items,
            'status' => $status,
        ]);

        return $purchaseOrder;
    }

    private static function deriveReceivedStatus(array $items): string
    {
        if (empty($items)) {
            return 'sent';
        }

        $anyReceived = false;
        $allReceived = true;

        foreach ($items as $item) {
            $qty      = (float) ($item['quantity'] ?? 0);
            $received = (float) ($item['received_quantity'] ?? 0);

            if ($received > 0) {
                $anyReceived = true;
            }

            if ($received < $qty) {
                $allReceived = false;
            }
        }

        if ($allReceived) {
            return 'received';
        }

        if ($anyReceived) {
            return 'partially_received';
        }

        return 'sent';
    }

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
