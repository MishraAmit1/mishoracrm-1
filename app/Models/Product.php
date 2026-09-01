<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'product_code',
        'description',
        'rate',
        'cost_price',
        'tax_percent',
        'hsn',
        'unit',
        'is_active',
        'type',
        'category',
        'current_stock',
        'reserved_stock',
        'reorder_level',
        'reorder_quantity',
    ];

    protected $casts = [
        'rate'              => 'decimal:2',
        'cost_price'        => 'decimal:2',
        'tax_percent'       => 'decimal:2',
        'is_active'         => 'boolean',
        'current_stock'     => 'decimal:2',
        'reserved_stock'    => 'decimal:2',
        'reorder_level'     => 'decimal:2',
        'reorder_quantity'  => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ── Relationships ─────────────────────────────────────────────

    // BOM rows where THIS product is the finished good being built.
    public function billOfMaterials(): HasMany
    {
        return $this->hasMany(BillOfMaterialItem::class, 'product_id');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRawMaterial($query)
    {
        return $query->where('type', 'raw_material');
    }

    public function scopeFinishedGood($query)
    {
        return $query->where('type', 'finished_good');
    }

    public function scopeLowStock($query)
    {
        return $query->whereNotNull('reorder_level')
            ->whereColumn('current_stock', '<=', 'reorder_level');
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function isLowStock(): bool
    {
        return $this->reorder_level !== null && (float) $this->current_stock <= (float) $this->reorder_level;
    }

    // The per-unit cost used for BOM / production costing and inventory
    // valuation. Prefers the maintained weighted-average cost_price, falls
    // back to the selling rate when a product has never been costed
    // (older data, or stock only ever set manually).
    public function costBasis(): float
    {
        return (float) ($this->cost_price ?? $this->rate ?? 0);
    }

    // Value of the stock currently on hand at cost.
    public function inventoryValue(): float
    {
        return round((float) $this->current_stock * $this->costBasis(), 2);
    }

    // Adjusts current_stock by $delta (positive = increase, negative =
    // decrease), clamped so stock never goes negative. Done as a single
    // atomic UPDATE (current_stock computed from the DB row, not the
    // possibly-stale in-memory value) so concurrent sales/receipts on the
    // same product can't silently clobber each other's stock change.
    public function adjustStock(float $delta): void
    {
        static::query()
            ->where($this->getKeyName(), $this->getKey())
            ->update([
                'current_stock' => DB::raw('GREATEST(0, current_stock + (' . (float) $delta . '))'),
            ]);

        $this->refresh();
    }

    // Stock not already earmarked for open Work Orders.
    public function availableStock(): float
    {
        return round((float) $this->current_stock - (float) $this->reserved_stock, 2);
    }

    // Earmark $qty for an open Work Order (can push availableStock negative,
    // which signals the shop is oversubscribed). Atomic, like adjustStock.
    public function reserve(float $qty): void
    {
        if ($qty == 0) {
            return;
        }

        static::query()
            ->where($this->getKeyName(), $this->getKey())
            ->update(['reserved_stock' => DB::raw('reserved_stock + (' . (float) $qty . ')')]);

        $this->refresh();
    }

    // Give back a reservation (clamped at 0 so we can't go negative).
    public function releaseReservation(float $qty): void
    {
        if ($qty == 0) {
            return;
        }

        static::query()
            ->where($this->getKeyName(), $this->getKey())
            ->update(['reserved_stock' => DB::raw('GREATEST(0, reserved_stock - (' . (float) $qty . '))')]);

        $this->refresh();
    }
}
