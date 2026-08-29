<?php

namespace App\Models;

use App\BelongsToTenant;
use App\HasAuditLog;
use App\HasGstBreakdown;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VendorBill extends Model
{
    use SoftDeletes, BelongsToTenant, HasAuditLog, HasFactory, HasGstBreakdown;

    protected $fillable = [
        'tenant_id',
        'vendor_id',
        'purchase_order_id',
        'number',
        'vendor_invoice_number',
        'date',
        'due_date',
        'items',
        'subtotal',
        'discount',
        'tax_percent',
        'tax_amount',
        'place_of_supply',
        'is_inter_state',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'total',
        'amount_paid',
        'notes',
        'status',
        'overdue_notified_at',
        'created_by',
    ];

    protected $casts = [
        'items'               => 'array',
        'date'                => 'date',
        'due_date'            => 'date',
        'overdue_notified_at' => 'datetime',
        'subtotal'            => 'decimal:2',
        'discount'            => 'decimal:2',
        'tax_percent'         => 'decimal:2',
        'tax_amount'          => 'decimal:2',
        'is_inter_state'      => 'boolean',
        'cgst_amount'         => 'decimal:2',
        'sgst_amount'         => 'decimal:2',
        'igst_amount'         => 'decimal:2',
        'total'               => 'decimal:2',
        'amount_paid'         => 'decimal:2',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(VendorBillPayment::class)->latest('paid_at');
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeOutstanding($query)
    {
        return $query->whereIn('status', ['unpaid', 'partially_paid']);
    }

    public function scopeOverdue($query)
    {
        return $query->whereIn('status', ['unpaid', 'partially_paid'])
                     ->whereNotNull('due_date')
                     ->whereDate('due_date', '<', now()->toDateString());
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function getDueAmountAttribute(): float
    {
        return round((float) $this->total - (float) $this->amount_paid, 2);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['unpaid', 'partially_paid'], true)
            && (float) $this->amount_paid === 0.0;
    }

    public function isOverdue(): bool
    {
        return in_array($this->status, ['unpaid', 'partially_paid'], true)
            && $this->due_date
            && $this->due_date->isPast();
    }

    public function getFormattedTotalAttribute(): string
    {
        return '₹' . number_format($this->total, 2);
    }

    // ── Auto generate number ──────────────────────────────────────
    public static function generateNumber(?int $tenantId = null): string
    {
        $tenantId = $tenantId ?? auth()->user()->tenant_id;

        $lastId = static::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->max('id') ?? 0;

        $num = str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
        return 'BILL-' . now()->format('Ymd') . '-' . $num;
    }

    // Same math as PurchaseOrder / Quotation — per-row tax with an overall
    // discount applied pro-rata.
    public static function calculateTotals(array $items, float $discount = 0, float $taxPercent = 0): array
    {
        return PurchaseOrder::calculateTotals($items, $discount, $taxPercent);
    }

    // ── Static helpers ────────────────────────────────────────────

    public static function statuses(): array
    {
        return [
            'unpaid'         => 'Unpaid',
            'partially_paid' => 'Partially Paid',
            'paid'           => 'Paid',
            'cancelled'      => 'Cancelled',
        ];
    }

    public static function paymentMethods(): array
    {
        return config('crm.invoice.payment_methods');
    }

    // Re-derive status from the paid total (never overrides a cancelled bill).
    public function deriveStatusFromPayments(): string
    {
        if ($this->status === 'cancelled') {
            return 'cancelled';
        }

        $paid = (float) $this->amount_paid;

        if ($paid <= 0) {
            return 'unpaid';
        }

        return $paid + 0.01 >= (float) $this->total ? 'paid' : 'partially_paid';
    }
}
