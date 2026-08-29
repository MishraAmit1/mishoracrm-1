<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductBatch extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'batch_number',
        'quantity',
        'initial_quantity',
        'unit_cost',
        'expiry_date',
        'received_at',
        'purchase_order_id',
        'work_order_id',
        'notes',
        'expiry_notified_at',
    ];

    protected $casts = [
        'quantity'            => 'decimal:2',
        'initial_quantity'    => 'decimal:2',
        'unit_cost'           => 'decimal:2',
        'expiry_date'         => 'date',
        'received_at'         => 'date',
        'expiry_notified_at'  => 'datetime',
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

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeExpiringWithin($query, int $days)
    {
        return $query->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->addDays($days)->toDateString())
            ->whereDate('expiry_date', '>=', now()->toDateString())
            ->where('quantity', '>', 0);
    }

    public function scopeHasStock($query)
    {
        return $query->where('quantity', '>', 0);
    }
}
