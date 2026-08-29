<?php

namespace App\Models;

use App\BelongsToTenant;
use App\HasAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoodsReceiptNote extends Model
{
    use SoftDeletes, BelongsToTenant, HasAuditLog;

    protected $fillable = [
        'tenant_id',
        'purchase_order_id',
        'vendor_id',
        'number',
        'received_date',
        'note',
        'items',
        'created_by',
    ];

    protected $casts = [
        'items'         => 'array',
        'received_date' => 'date',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Totals across this GRN's lines ────────────────────────────
    public function totalReceived(): float
    {
        return round(collect($this->items ?? [])->sum(fn ($i) => (float) ($i['received_qty'] ?? 0)), 2);
    }

    public function totalAccepted(): float
    {
        return round(collect($this->items ?? [])->sum(fn ($i) => (float) ($i['accepted_qty'] ?? 0)), 2);
    }

    public function totalRejected(): float
    {
        return round(collect($this->items ?? [])->sum(fn ($i) => (float) ($i['rejected_qty'] ?? 0)), 2);
    }

    public function hasRejections(): bool
    {
        return $this->totalRejected() > 0;
    }

    public static function generateNumber(int $tenantId): string
    {
        $lastId = static::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->max('id') ?? 0;

        return 'GRN-' . now()->format('Ymd') . '-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
    }
}
