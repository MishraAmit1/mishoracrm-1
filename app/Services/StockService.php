<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;

class StockService
{
    // ── Apply invoice item quantities to finished-good stock ────────
    // $sign = -1 on sale (Invoice create / re-apply after update),
    // $sign = +1 to undo/restore (Invoice update's "reverse old items"
    // step, or Invoice destroy). Only rows carrying a product_id move
    // stock — free-text invoice lines are ignored.
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

            $product->adjustStock($sign * $qty);

            // Only fire the low-stock alert on the decrementing (sale) side,
            // and only the moment it first crosses the line.
            if ($sign < 0 && !$wasLow && $product->fresh()->isLowStock()) {
                static::notifyLowStock($product->fresh());
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
