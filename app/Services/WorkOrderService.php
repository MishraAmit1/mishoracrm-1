<?php

namespace App\Services;

use App\Models\Product;
use App\Models\WorkOrder;
use App\Models\WorkOrderStage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkOrderService
{
    // ── Create — collision-safe number generation, then earmark the BOM
    // raw materials so a second Work Order can't be planned against
    // stock this one already needs. ─────────────────────────────────
    public static function store(array $data, int $tenantId, int $userId): WorkOrder
    {
        $stages = $data['stages'] ?? [];
        unset($data['stages']);

        $workOrder = retry(3, function () use ($data, $tenantId, $userId) {
            return WorkOrder::create(array_merge($data, [
                'tenant_id'  => $tenantId,
                'number'     => WorkOrder::generateNumber(),
                'created_by' => $userId,
                'status'     => 'pending',
            ]));
        }, 50, fn ($e) => static::isNumberCollision($e));

        static::syncStages($workOrder, $stages);

        if ($workOrder->product) {
            StockService::reserveForProduction($workOrder->product, (float) $workOrder->quantity);
        }

        return $workOrder;
    }

    // Replace a Work Order's routing stages (create + edit, pending only).
    // Rows: [{ name, assigned_to }]. Sequence is the row order.
    public static function syncStages(WorkOrder $workOrder, array $stages): void
    {
        $workOrder->stages()->delete();

        $seq = 1;
        foreach ($stages as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $workOrder->stages()->create([
                'tenant_id'   => $workOrder->tenant_id,
                'sequence'    => $seq++,
                'name'        => $name,
                'assigned_to' => $row['assigned_to'] ?? null,
                'status'      => 'pending',
            ]);
        }
    }

    // ── Update — only reachable while pending (isEditable, enforced by
    // policy). Re-point the reservation at the new product/quantity. ──
    public static function update(WorkOrder $workOrder, array $data): WorkOrder
    {
        $stages = $data['stages'] ?? null;
        unset($data['stages']);

        return DB::transaction(function () use ($workOrder, $data, $stages) {
            $oldProduct = $workOrder->product;
            $oldQty     = (float) $workOrder->quantity;

            if (!$workOrder->materialsIssued() && $oldProduct) {
                StockService::releaseProductionReservation($oldProduct, $oldQty);
            }

            $workOrder->update($data);
            $workOrder->refresh()->load('product.billOfMaterials.material');

            if ($stages !== null) {
                static::syncStages($workOrder, $stages);
            }

            if (!$workOrder->materialsIssued() && $workOrder->product) {
                StockService::reserveForProduction($workOrder->product, (float) $workOrder->quantity);
            }

            return $workOrder;
        });
    }

    // ── Start production — pending → in_progress AND issue materials to
    // the shop floor. The reservation becomes an actual FEFO consumption;
    // material cost is frozen here (WIP: materials gone, goods not yet in).
    // Blocks if physical raw-material stock is short. ──────────────────
    public static function start(WorkOrder $workOrder): WorkOrder
    {
        $product = $workOrder->product;

        static::assertNoShortfall($product, (float) $workOrder->quantity, 'start');

        return DB::transaction(function () use ($workOrder, $product) {
            $materialCostSnapshot = $workOrder->calculateLiveMaterialCost();

            StockService::issueForProduction($product, (float) $workOrder->quantity);

            $workOrder->update([
                'status'                 => 'in_progress',
                'started_at'             => now(),
                'materials_issued_at'    => now(),
                'material_cost_snapshot' => $materialCostSnapshot,
            ]);

            return $workOrder;
        });
    }

    // ── Complete → completed, credit the finished good's stock.
    //  - If materials were already issued at start (the normal WIP flow),
    //    just credit the finished good using the frozen cost.
    //  - If not (a Work Order taken straight to complete), fall back to
    //    the old behaviour: verify stock, consume materials now, freeze
    //    cost, then credit. ──────────────────────────────────────────
    public static function complete(
        WorkOrder $workOrder,
        ?string $finishedGoodExpiryDate = null,
        ?float $producedQuantity = null,
        ?float $scrapQuantity = null,
        ?string $scrapReason = null
    ): WorkOrder {
        if (!$workOrder->allStagesFinished()) {
            throw ValidationException::withMessages([
                'quantity' => 'Finish (or skip) every production stage before completing this Work Order.',
            ]);
        }

        $product  = $workOrder->product;
        $qty      = (float) $workOrder->quantity;
        $produced = $producedQuantity !== null ? max(0.0, $producedQuantity) : $qty;
        $scrap    = $scrapQuantity !== null ? max(0.0, $scrapQuantity) : 0.0;

        return DB::transaction(function () use ($workOrder, $product, $qty, $produced, $scrap, $scrapReason, $finishedGoodExpiryDate) {
            $completionFields = [
                'status'            => 'completed',
                'completed_at'      => now(),
                'produced_quantity' => $produced,
                'scrap_quantity'    => $scrap,
                'scrap_reason'      => $scrap > 0 ? $scrapReason : null,
            ];

            if ($workOrder->materialsIssued()) {
                $materialCostSnapshot = $workOrder->material_cost_snapshot !== null
                    ? (float) $workOrder->material_cost_snapshot
                    : $workOrder->calculateLiveMaterialCost();

                $finishedUnitCost = static::finishedUnitCost($workOrder, $materialCostSnapshot, $produced);

                if ($produced > 0) {
                    StockService::receiveBatch($product, $produced, null, $finishedGoodExpiryDate, null, $workOrder->id, $finishedUnitCost);
                }

                $workOrder->update($completionFields + ['material_cost_snapshot' => $materialCostSnapshot]);

                return $workOrder;
            }

            // Legacy straight-to-complete path — materials never issued.
            static::assertNoShortfall($product, $qty, 'complete');

            $materialCostSnapshot = $workOrder->calculateLiveMaterialCost();
            $finishedUnitCost     = static::finishedUnitCost($workOrder, $materialCostSnapshot, $produced);

            StockService::releaseProductionReservation($product, $qty);
            StockService::consumeForProduction($product, $qty, $workOrder->id, $finishedGoodExpiryDate, $finishedUnitCost, $produced);

            $workOrder->update($completionFields + ['material_cost_snapshot' => $materialCostSnapshot]);

            return $workOrder;
        });
    }

    // ── Cancel — release the reservation, or (if materials were already
    // issued) return them to stock since nothing was produced. ────────
    public static function cancel(WorkOrder $workOrder): WorkOrder
    {
        return DB::transaction(function () use ($workOrder) {
            $product = $workOrder->product;

            if ($product) {
                if ($workOrder->materialsIssued()) {
                    StockService::returnIssuedMaterials($product, (float) $workOrder->quantity);
                } elseif (in_array($workOrder->status, ['pending', 'in_progress'], true)) {
                    StockService::releaseProductionReservation($product, (float) $workOrder->quantity);
                }
            }

            $workOrder->update([
                'status'              => 'cancelled',
                'materials_issued_at' => null,
            ]);

            return $workOrder;
        });
    }

    // ── Release a pending Work Order's reservation right before it is
    // deleted (called by the controller's destroy). ──────────────────
    public static function releaseOnDelete(WorkOrder $workOrder): void
    {
        if (!$workOrder->materialsIssued()
            && in_array($workOrder->status, ['pending', 'in_progress'], true)
            && $workOrder->product) {
            StockService::releaseProductionReservation($workOrder->product, (float) $workOrder->quantity);
        }
    }

    // ── Routing stage transitions ─────────────────────────────────────
    // Stages are only actionable once production has started (materials
    // issued / status in_progress).
    public static function startStage(WorkOrderStage $stage): WorkOrderStage
    {
        static::assertStageActionable($stage);

        if (!$stage->isPending()) {
            throw ValidationException::withMessages(['stage' => 'Only a pending stage can be started.']);
        }

        $stage->update(['status' => 'in_progress', 'started_at' => now()]);

        return $stage;
    }

    public static function completeStage(WorkOrderStage $stage): WorkOrderStage
    {
        static::assertStageActionable($stage);

        if ($stage->isFinished()) {
            throw ValidationException::withMessages(['stage' => 'This stage is already finished.']);
        }

        $stage->update([
            'status'       => 'done',
            'started_at'   => $stage->started_at ?? now(),
            'completed_at' => now(),
        ]);

        return $stage;
    }

    public static function skipStage(WorkOrderStage $stage): WorkOrderStage
    {
        static::assertStageActionable($stage);

        $stage->update(['status' => 'skipped', 'completed_at' => now()]);

        return $stage;
    }

    public static function reopenStage(WorkOrderStage $stage): WorkOrderStage
    {
        static::assertStageActionable($stage);

        $stage->update(['status' => 'in_progress', 'completed_at' => null]);

        return $stage;
    }

    private static function assertStageActionable(WorkOrderStage $stage): void
    {
        if (!$stage->workOrder->isInProgress()) {
            throw ValidationException::withMessages([
                'stage' => 'Start production first — stages become actionable once the Work Order is in progress.',
            ]);
        }
    }

    // ── Preview shortfall for the create/show forms (does not mutate) ──
    public static function previewShortfall(Product $product, float $quantity): array
    {
        return StockService::productionShortfallFor($product, $quantity);
    }

    private static function finishedUnitCost(WorkOrder $workOrder, float $materialCost, float $qty): float
    {
        return $qty > 0
            ? ($materialCost + (float) $workOrder->labor_cost + (float) $workOrder->machine_cost) / $qty
            : 0.0;
    }

    private static function assertNoShortfall(?Product $product, float $qty, string $verb): void
    {
        if (!$product) {
            return;
        }

        $shortfall = StockService::productionShortfallFor($product, $qty);

        if (!empty($shortfall)) {
            $lines = collect($shortfall)
                ->map(fn ($row) => "{$row['name']} (need {$row['needed']}, have {$row['available']})")
                ->implode('; ');

            throw ValidationException::withMessages([
                'quantity' => "Cannot {$verb} — insufficient raw material stock: {$lines}",
            ]);
        }
    }

    private static function isNumberCollision(\Throwable $e): bool
    {
        return $e instanceof \Illuminate\Database\QueryException
            && str_contains(strtolower($e->getMessage()), 'number');
    }
}
