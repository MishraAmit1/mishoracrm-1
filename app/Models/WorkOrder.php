<?php

namespace App\Models;

use App\BelongsToTenant;
use App\HasAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkOrder extends Model
{
    use SoftDeletes, BelongsToTenant, HasAuditLog;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'number',
        'quantity',
        'status',
        'started_at',
        'completed_at',
        'notes',
        'created_by',
        'labor_cost',
        'machine_cost',
    ];

    protected $casts = [
        'quantity'     => 'decimal:2',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
        'labor_cost'   => 'decimal:2',
        'machine_cost' => 'decimal:2',
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
    // Material cost is computed live from the product's current BOM ×
    // each material's current rate — this is an approximation (not a
    // snapshot), so it will drift if rates/BOM change after the work
    // order is created. Good enough for a quick cost/margin read; not
    // meant as an immutable historical ledger entry.
    public function getMaterialCostAttribute(): float
    {
        $this->loadMissing('product.billOfMaterials.material');

        return (float) $this->product?->billOfMaterials
            ?->sum(fn ($bomItem) => (float) $bomItem->quantity_per_unit * (float) $this->quantity * (float) ($bomItem->material->rate ?? 0));
    }

    public function getTotalCostAttribute(): float
    {
        return $this->material_cost + (float) $this->labor_cost + (float) $this->machine_cost;
    }

    public function getCostPerUnitAttribute(): float
    {
        return (float) $this->quantity > 0 ? $this->total_cost / (float) $this->quantity : 0.0;
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
