<?php

namespace App\Models;

use App\BelongsToTenant;
use App\HasAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes, BelongsToTenant, HasAuditLog;

    protected $fillable = [
        'tenant_id',
        'contact_id',
        'quotation_id',
        'appointment_id',
        'number',
        'date',
        'due_date',
        'items',
        'subtotal',
        'discount',
        'tax_percent',
        'tax_amount',
        'total',
        'currency',
        'paid_amount',
        'notes',
        'terms',
        'status',
        'razorpay_payment_id',
        'paid_at',
        'due_reminded_at',
        'overdue_reminded_at',
        'created_by',
    ];

    protected $casts = [
        'items'       => 'array',
        'date'        => 'date',
        'due_date'    => 'date',
        'paid_at'     => 'datetime',
        'due_reminded_at'     => 'datetime',
        'overdue_reminded_at' => 'datetime',
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

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class)->latest('paid_at');
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

    public function scopeDueForPaymentReminder($query)
    {
        return $query->whereIn('status', ['sent', 'partial'])
                     ->whereNull('due_reminded_at')
                     ->whereBetween('due_date', [now()->startOfDay(), now()->addDays(3)->endOfDay()]);
    }

    public function scopeOverdueForPaymentReminder($query)
    {
        return $query->whereIn('status', ['sent', 'partial'])
                     ->whereNull('overdue_reminded_at')
                     ->where('due_date', '<', now()->startOfDay());
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
        return $this->currencySymbol() . number_format($this->total, 2);
    }

    public function currencySymbol(): string
    {
        return config("quotation.currencies.{$this->currency}.symbol", $this->currency ?? '₹');
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
    // Accepts an explicit tenant id for contexts with no authenticated user
    // (e.g. the customer self-serve accept flow), falling back to auth().
    public static function generateNumber(?int $tenantId = null): string
    {
        $tenantId = $tenantId ?? auth()->user()->tenant_id;

        $lastId = static::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->max('id') ?? 0;

        $num = str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
        return 'INV-' . now()->format('Ymd') . '-' . $num;
    }

    // ── Calculate totals from items ───────────────────────────────
    public static function calculateTotals(array $items, float $discount = 0, float $taxPercent = 18): array
    {
        $subtotal = 0;
        $taxAmount = 0;

        foreach ($items as $item) {
            $qty     = (float) ($item['quantity'] ?? 0);
            $rate    = (float) ($item['rate']     ?? 0);
            $rowAmt  = $qty * $rate;
            $subtotal += $rowAmt;

            // Use per-item tax_percent if stored, fall back to invoice-level
            $itemTax = isset($item['tax_percent']) ? (float) $item['tax_percent'] : $taxPercent;
            $taxAmount += $rowAmt * $itemTax / 100;
        }

        $discountedSubtotal = $subtotal - $discount;
        $discRatio  = $subtotal > 0 ? $discountedSubtotal / $subtotal : 1;
        $taxAmount  = round($taxAmount * $discRatio, 2);
        $total      = round($discountedSubtotal + $taxAmount, 2);

        // Effective tax percent for display
        $effectiveTaxPct = $discountedSubtotal > 0 ? round($taxAmount / $discountedSubtotal * 100, 2) : $taxPercent;

        return [
            'subtotal'    => round($subtotal, 2),
            'discount'    => round($discount, 2),
            'tax_percent' => $effectiveTaxPct,
            'tax_amount'  => $taxAmount,
            'total'       => $total,
        ];
    }

    // ── Static helpers ────────────────────────────────────────────

    public static function statuses(): array
    {
        return config('crm.invoice.statuses');
    }

    public static function paymentMethods(): array
    {
        return config('crm.invoice.payment_methods');
    }
}