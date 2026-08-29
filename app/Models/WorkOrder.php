<?php

namespace App\Models;

use App\BelongsToTenant;
use App\HasAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkOrder extends Model
{
    use SoftDeletes, BelongsToTenant, HasAuditLog;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'assigned_to',
        'number',
        'quantity',
        'produced_quantity',
        'scrap_quantity',
        'scrap_reason',
        'status',
        'started_at',
        'materials_issued_at',
        'completed_at',
        'notes',
        'created_by',
        'labor_cost',
        'machine_cost',
        'material_cost_snapshot',
    ];

    protected $casts = [
        'quantity'            => 'decimal:2',
        'produced_quantity'   => 'decimal:2',
        'scrap_quantity'      => 'decimal:2',
        'started_at'          => 'datetime',
        'materials_issued_at' => 'datetime',
        'completed_at'        => 'datetime',
        'labor_cost'   => 'decimal:2',
        'machine_cost' => 'decimal:2',
        'material_cost_snapshot' => 'decimal:2',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function stages(): HasMany
    {
        return $this->hasMany(WorkOrderStage::class)->orderBy('sequence');
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    // Raw materials have physically left stock into WIP.
    public function materialsIssued(): bool
    {
        return $this->materials_issued_at !== null;
    }

    public function hasStages(): bool
    {
        return $this->stages()->exists();
    }

    // Every routing stage is done or skipped (true also when there are no
    // stages at all).
    public function allStagesFinished(): bool
    {
        return !$this->stages()
            ->whereIn('status', ['pending', 'in_progress'])
            ->exists();
    }

    // Editable/deletable only before production has actually started.
    public function isEditable(): bool
    {
        return $this->status === 'pending';
    }

    public static function generateNumber(): string
    {
        $lastId = static::withoutGlobalScopes()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->max('id') ?? 0;

        $num = str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
        return 'WO-' . now()->format('Ymd') . '-' . $num;
    }

    // ── Costing ───────────────────────────────────────────────────
    // Once completed, material cost is FROZEN in material_cost_snapshot
    // (set by WorkOrderService::complete() at the moment stock is
    // consumed) so later BOM/rate changes never retroactively alter a
    // finished Work Order's recorded cost. While still pending/in_progress
    // there's nothing to freeze yet, so this falls back to a live BOM
    // calculation — a preview only, not a committed figure.
    public function getMaterialCostAttribute(): float
    {
        if ($this->material_cost_snapshot !== null) {
            return (float) $this->material_cost_snapshot;
        }

        return $this->calculateLiveMaterialCost();
    }

    public function calculateLiveMaterialCost(): float
    {
        $this->loadMissing('product.billOfMaterials.material');

        return (float) $this->product?->billOfMaterials
            ?->sum(fn ($bomItem) => (float) $bomItem->quantity_per_unit * (float) $this->quantity * (float) ($bomItem->material?->costBasis() ?? 0));
    }

    public function getTotalCostAttribute(): float
    {
        return $this->material_cost + (float) $this->labor_cost + (float) $this->machine_cost;
    }

    // Good units produced — falls back to the planned quantity until the
    // Work Order is completed with an explicit figure.
    public function yieldQuantity(): float
    {
        return $this->produced_quantity !== null
            ? (float) $this->produced_quantity
            : (float) $this->quantity;
    }

    // Total run cost is absorbed by the good output, so scrap pushes
    // cost/unit up.
    public function getCostPerUnitAttribute(): float
    {
        return $this->yieldQuantity() > 0 ? $this->total_cost / $this->yieldQuantity() : 0.0;
    }

    // ── Margin ────────────────────────────────────────────────────
    // Compares production cost against the product's current selling
    // rate — a quick profitability read, not a formal accounting figure
    // (it uses today's Product.rate, not the rate at time of sale).
    public function getSellingValueAttribute(): float
    {
        return (float) ($this->product?->rate ?? 0) * $this->yieldQuantity();
    }

    public function getMarginAttribute(): float
    {
        return $this->selling_value - $this->total_cost;
    }

    public function getMarginPercentAttribute(): float
    {
        return $this->selling_value > 0 ? round($this->margin / $this->selling_value * 100, 1) : 0.0;
    }

    public static function statuses(): array
    {
        return [
            'pending'     => 'Pending',
            'in_progress' => 'In Progress',
            'completed'   => 'Completed',
            'cancelled'   => 'Cancelled',
        ];
    }
}
