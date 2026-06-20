<?php

namespace App\Models;

use App\BelongsToTenant;
use App\HasAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes, BelongsToTenant, HasAuditLog;

    protected $fillable = [
        'tenant_id',
        'contact_id',
        'quotation_id',
        'number',
        'date',
        'due_date',
        'items',
        'subtotal',
        'discount',
        'tax_percent',
        'tax_amount',
        'total',
        'paid_amount',
        'notes',
        'terms',
        'status',
        'razorpay_payment_id',
        'paid_at',
        'created_by',
    ];

    protected $casts = [
        'items'       => 'array',
        'date'        => 'date',
        'due_date'    => 'date',
        'paid_at'     => 'datetime',
        'subtotal'    => 'decimal:2',
        'discount'    => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'tax_amount'  => 'decimal:2',
        'total'       => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
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

    public function scopeOverdue($query)
    {
        return $query->whereIn('status', ['sent', 'partial'])
                     ->where('due_date', '<', now());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                     ->whereYear('created_at', now()->year);
    }

    public function scopePaidThisMonth($query)
    {
        return $query->where('status', 'paid')
                     ->whereMonth('paid_at', now()->month)
                     ->whereYear('paid_at', now()->year);
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isOverdue(): bool
    {
        return in_array($this->status, ['sent', 'partial'])
            && $this->due_date
            && $this->due_date->isPast();
    }

    public function getDueAmountAttribute(): float
    {
        return round($this->total - $this->paid_amount, 2);
    }

    public function getFormattedTotalAttribute(): string
    {
        return '₹' . number_format($this->total, 2);
    }

    public function getFormattedPaidAttribute(): string
    {
        return '₹' . number_format($this->paid_amount, 2);
    }

    public function getFormattedDueAttribute(): string
    {
        return '₹' . number_format($this->due_amount, 2);
    }

    // ── Auto generate invoice number ──────────────────────────────
    public static function generateNumber(): string
    {
        $lastId = static::withoutGlobalScopes()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->max('id') ?? 0;

        $num = str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
        return 'INV-' . now()->format('Ymd') . '-' . $num;
    }

    // ── Calculate totals from items ───────────────────────────────
    public static function calculateTotals(array $items, float $discount = 0, float $taxPercent = 18): array
    {
        $subtotal = collect($items)->sum(fn($item) =>
            ($item['quantity'] ?? 0) * ($item['rate'] ?? 0)
        );

        $discountedSubtotal = $subtotal - $discount;
        $taxAmount          = ($discountedSubtotal * $taxPercent) / 100;
        $total              = $discountedSubtotal + $taxAmount;

        return [
            'subtotal'    => round($subtotal, 2),
            'discount'    => round($discount, 2),
            'tax_percent' => $taxPercent,
            'tax_amount'  => round($taxAmount, 2),
            'total'       => round($total, 2),
        ];
    }

    // ── Static helpers ────────────────────────────────────────────

    public static function statuses(): array
    {
        return config('crm.invoice.statuses');
    }
}