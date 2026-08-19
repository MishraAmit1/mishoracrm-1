<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\User;
use Illuminate\Support\Collection;

class StockService
{
    // ── Apply invoice item quantities to finished-good stock ────────
    // $sign = -1 on sale (Invoice create / re-apply after update),
    // $sign = +1 to undo/restore (Invoice update's "reverse old items"
    // step, or Invoice destroy). Only rows carrying a product_id move
    // stock — free-text invoice lines are ignored. Sales consume via
    // FEFO batches; restores land in a fresh no-expiry "adjustment"
    // batch rather than attempting to reconstruct original attribution.
    public static function applyInvoiceItems(array $items, int $sign): void
    {
        foreach ($items as $item) {
            $productId = $item['product_id'] ?? null;
            if (empty($productId)) {
                continue;
            }

            $product = Product::find($productId);
            if (!$product) {
                continue;
            }

            $qty = (float) ($item['quantity'] ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $wasLow = $product->isLowStock();

            if ($sign < 0) {
                static::consumeBatchesFEFO($product, $qty);
                static::consumeBomForSale($product, $qty);
            } else {
                static::receiveBatch($product, $qty, null, null);
                static::restoreBomForSale($product, $qty);
            }

            // Only fire the low-stock alert on the decrementing (sale) side,
            // and only the moment it first crosses the line.
            if ($sign < 0 && !$wasLow && $product->fresh()->isLowStock()) {
                static::notifyLowStock($product->fresh());
            }
        }
    }

    // ── Auto-consume BOM raw materials the moment a finished good is
    // sold on an invoice — no separate Work Order needed. This is the
    // make-to-order path: a small manufacturer who doesn't run formal
    // production batches just wants "sell 1 shampoo, deduct its bottle/
    // fragrance/base materials automatically". A product with no BOM
    // rows (or a raw material itself) is a no-op. Lenient like the
    // finished good's own stock: does not block the sale if a material
    // runs short, just clamps at 0 (consumeBatchesFEFO's guarantee) —
    // consistent with the existing oversell-allowed invoice behaviour. ──
    public static function consumeBomForSale(Product $product, float $quantity): void
    {
        if ($quantity <= 0) {
            return;
        }

        $product->loadMissing('billOfMaterials.material');

        foreach ($product->billOfMaterials as $bomItem) {
            $material = $bomItem->material;
            if (!$material) {
                continue;
            }

            $needed = (float) $bomItem->quantity_per_unit * $quantity;
            if ($needed <= 0) {
                continue;
            }

            $wasLow = $material->isLowStock();
            static::consumeBatchesFEFO($material, $needed);

            if (!$wasLow && $material->fresh()->isLowStock()) {
                static::notifyLowStock($material->fresh());
            }
        }
    }

    // ── Reverse of consumeBomForSale — restores BOM raw materials when
    // a sale is edited down or the invoice is deleted. ──────────────────
    public static function restoreBomForSale(Product $product, float $quantity): void
    {
        if ($quantity <= 0) {
            return;
        }

        $product->loadMissing('billOfMaterials.material');

        foreach ($product->billOfMaterials as $bomItem) {
            $material = $bomItem->material;
            if (!$material) {
                continue;
            }

            $restored = (float) $bomItem->quantity_per_unit * $quantity;
            if ($restored > 0) {
                static::receiveBatch($material, $restored, null, null);
            }
        }
    }

    // ── Apply a received-quantity delta to a raw material's stock ───
    // (used by PurchaseOrderService::receive()).
    public static function applyReceivedDelta(int $productId, float $delta): void
    {
        if ($delta == 0) {
            return;
        }

        $product = Product::find($productId);
        $product?->adjustStock($delta);
    }

    // ── BOM explosion — what raw materials (and how much) are needed
    // to replenish a finished good back up to its reorder quantity ──
    public static function suggestedMaterialsFor(Product $product): array
    {
        // A raw material has no BOM of its own to explode — if it's the
        // thing that's short, suggest restocking it directly instead.
        if ($product->type === 'raw_material') {
            $shortfall = max(0, (float) ($product->reorder_quantity ?: 0) - (float) $product->current_stock);

            if ($shortfall <= 0) {
                return [];
            }

            return [[
                'material_id' => $product->id,
                'name'        => $product->name,
                'description' => $product->description,
                'quantity'    => round($shortfall, 2),
                'reason'      => "Direct restock — below reorder level",
            ]];
        }

        $rows = [];

        $product->loadMissing('billOfMaterials.material');

        foreach ($product->billOfMaterials as $bomItem) {
            $material = $bomItem->material;
            if (!$material) {
                continue;
            }

            $needed    = (float) $bomItem->quantity_per_unit * (float) ($product->reorder_quantity ?: 1);
            $shortfall = max(0, $needed - (float) $material->current_stock);

            if ($shortfall <= 0) {
                continue;
            }

            $rows[] = [
                'material_id' => $material->id,
                'name'        => $material->name,
                'description' => $material->description,
                'quantity'    => round($shortfall, 2),
                'reason'      => "Restock for {$product->name} (BOM suggestion)",
            ];
        }

        return $rows;
    }

    // ── Raw-material shortfall for building $quantity units of a
    // finished good (used by WorkOrderService before allowing a work
    // order to complete). Returns one row per BOM line that is short,
    // empty array if every material has enough stock. ────────────────
    public static function productionShortfallFor(Product $product, float $quantity): array
    {
        $product->loadMissing('billOfMaterials.material');

        $rows = [];

        foreach ($product->billOfMaterials as $bomItem) {
            $material = $bomItem->material;
            if (!$material) {
                continue;
            }

            $needed    = (float) $bomItem->quantity_per_unit * $quantity;
            $available = (float) $material->current_stock;

            if ($needed > $available) {
                $rows[] = [
                    'material_id' => $material->id,
                    'name'        => $material->name,
                    'needed'      => round($needed, 2),
                    'available'   => round($available, 2),
                    'shortfall'   => round($needed - $available, 2),
                ];
            }
        }

        return $rows;
    }

    // ── Consume raw materials per BOM (FEFO, batch-tracked) to build
    // $quantity units of a finished good, and credit the finished good's
    // own stock as a new batch. Caller (WorkOrderService::complete) must
    // have already verified sufficient stock via productionShortfallFor
    // — this does not re-check. ──────────────────────────────────────
    public static function consumeForProduction(Product $product, float $quantity, ?int $workOrderId = null, ?string $finishedGoodExpiryDate = null): void
    {
        $product->loadMissing('billOfMaterials.material');

        foreach ($product->billOfMaterials as $bomItem) {
            $material = $bomItem->material;
            if (!$material) {
                continue;
            }

            $needed = (float) $bomItem->quantity_per_unit * $quantity;
            if ($needed > 0) {
                static::consumeBatchesFEFO($material, $needed);
            }
        }

        static::receiveBatch($product, $quantity, null, $finishedGoodExpiryDate, null, $workOrderId);
    }

    // ── Receive $qty of a product into a new batch (auto-numbered if
    // $batchNumber is blank) and credit the product's aggregate stock.
    // Used by PO receiving, Work Order completion, and invoice-restore. ──
    public static function receiveBatch(Product $product, float $qty, ?string $batchNumber, ?string $expiryDate, ?int $purchaseOrderId = null, ?int $workOrderId = null): ProductBatch
    {
        $batch = ProductBatch::create([
            'tenant_id'         => $product->tenant_id,
            'product_id'        => $product->id,
            'batch_number'      => $batchNumber ?: static::generateBatchNumber($product),
            'quantity'          => $qty,
            'initial_quantity'  => $qty,
            'expiry_date'       => $expiryDate,
            'received_at'       => now()->toDateString(),
            'purchase_order_id' => $purchaseOrderId,
            'work_order_id'     => $workOrderId,
        ]);

        $product->adjustStock($qty);

        return $batch;
    }

    // ── Consume $qty of a product FEFO across its batches (soonest
    // expiry first, no-expiry batches consumed last) for traceability.
    // The product's aggregate stock is always decremented by the full
    // $qty regardless of batch coverage — batches are a ledger on top
    // of, not a replacement for, current_stock (a product may carry
    // stock with no batch rows at all, e.g. set directly on creation).
    // Returns [{batch_id, batch_number, consumed_qty}, ...]. ────────────
    public static function consumeBatchesFEFO(Product $product, float $qty): array
    {
        $remaining = $qty;
        $consumed  = [];

        $batches = ProductBatch::withoutGlobalScopes()
            ->where('tenant_id', $product->tenant_id)
            ->where('product_id', $product->id)
            ->hasStock()
            ->orderByRaw('expiry_date IS NULL, expiry_date ASC')
            ->orderBy('received_at')
            ->get();

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $take = min($remaining, (float) $batch->quantity);
            if ($take <= 0) {
                continue;
            }

            $batch->decrement('quantity', $take);
            $consumed[] = [
                'batch_id'     => $batch->id,
                'batch_number' => $batch->batch_number,
                'consumed_qty' => round($take, 2),
            ];
            $remaining -= $take;
        }

        $product->adjustStock(-$qty);

        return $consumed;
    }

    private static function generateBatchNumber(Product $product): string
    {
        $prefix = $product->product_code ?: 'BATCH';
        $date   = now()->format('Ymd');

        $count = ProductBatch::withoutGlobalScopes()
            ->where('tenant_id', $product->tenant_id)
            ->where('product_id', $product->id)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        return strtoupper($prefix) . '-' . $date . '-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    }

    // ── Low-stock products for a tenant ──────────────────────────────
    public static function lowStockProducts(int $tenantId): Collection
    {
        return Product::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->lowStock()
            ->orderBy('name')
            ->get();
    }

    // ── Notify tenant admins that a product just crossed its reorder line ─
    private static function notifyLowStock(Product $product): void
    {
        $admins = User::withoutGlobalScopes()
            ->where('tenant_id', $product->tenant_id)
            ->where('user_type', 'tenant_admin')
            ->where('is_active', true)
            ->get();

        foreach ($admins as $admin) {
            app(NotificationService::class)->send(
                'product.low_stock',
                $admin,
                ['name' => $product->name, 'stock' => $product->current_stock],
                null,
                route('tenant.products.low-stock'),
                $product
            );
        }
    }
}
