<?php

namespace App\Services;

use App\Models\Product;
use App\Models\WorkOrder;
use Illuminate\Validation\ValidationException;

class WorkOrderService
{
    // ── Create — collision-safe number generation, same pattern as
    // PurchaseRequestService::store(). ──────────────────────────────
    public static function store(array $data, int $tenantId, int $userId): WorkOrder
    {
        return retry(3, function () use ($data, $tenantId, $userId) {
            return WorkOrder::create(array_merge($data, [
                'tenant_id'  => $tenantId,
                'number'     => WorkOrder::generateNumber(),
                'created_by' => $userId,
                'status'     => 'pending',
            ]));
        }, 50, fn ($e) => static::isNumberCollision($e));
    }

    // ── Update — only reachable while pending, enforced by policy ───
    public static function update(WorkOrder $workOrder, array $data): WorkOrder
    {
        $workOrder->update($data);

        return $workOrder;
    }

    // ── Start — pending → in_progress ────────────────────────────────
    public static function start(WorkOrder $workOrder): WorkOrder
    {
        $workOrder->update([
            'status'     => 'in_progress',
            'started_at' => now(),
        ]);

        return $workOrder;
    }

    // ── Complete — in_progress → completed. Blocks if any raw material
    // in the BOM is short (StockService::productionShortfallFor); if
    // sufficient, consumes raw materials and credits the finished good
    // (StockService::consumeForProduction). ─────────────────────────
    public static function complete(WorkOrder $workOrder, ?string $finishedGoodExpiryDate = null): WorkOrder
    {
        $product = $workOrder->product;

        $shortfall = StockService::productionShortfallFor($product, (float) $workOrder->quantity);

        if (!empty($shortfall)) {
            $lines = collect($shortfall)
                ->map(fn ($row) => "{$row['name']} (need {$row['needed']}, have {$row['available']})")
                ->implode('; ');

            throw ValidationException::withMessages([
                'quantity' => "Cannot complete — insufficient raw material stock: {$lines}",
            ]);
        }

        StockService::consumeForProduction($product, (float) $workOrder->quantity, $workOrder->id, $finishedGoodExpiryDate);

        $workOrder->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        return $workOrder;
    }

    // ── Cancel — pending/in_progress → cancelled. No stock effect since
    // consumption only happens at completion. ────────────────────────
    public static function cancel(WorkOrder $workOrder): WorkOrder
    {
        $workOrder->update(['status' => 'cancelled']);

        return $workOrder;
    }

    // ── Preview shortfall for the create/show forms (does not mutate) ──
    public static function previewShortfall(Product $product, float $quantity): array
    {
        return StockService::productionShortfallFor($product, $quantity);
    }

    private static function isNumberCollision(\Throwable $e): bool
    {
        return $e instanceof \Illuminate\Database\QueryException
            && str_contains(strtolower($e->getMessage()), 'number');
    }
}
