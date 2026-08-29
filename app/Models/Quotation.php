<?php

namespace App\Models;

use App\BelongsToTenant;
use App\HasAuditLog;
use App\HasGstBreakdown;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use SoftDeletes, BelongsToTenant, HasAuditLog, HasFactory, HasGstBreakdown;

    protected $fillable = [
        'tenant_id',
        'contact_id',
        'lead_id',
        'deal_id',
        'number',
        'date',
        'valid_until',
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
        'notes',
        'terms',
        'status',
        'created_by',
        'parent_quotation_id',
        'version',
        'currency',
        'public_token',
        'signed_name',
        'signature_data',
        'customer_response_ip',
        'customer_responded_at',
        'rejected_reason',
    ];

    protected $casts = [
        'items'                  => 'array',
        'date'                   => 'date',
        'valid_until'            => 'date',
        'subtotal'               => 'decimal:2',
        'discount'               => 'decimal:2',
        'tax_percent'            => 'decimal:2',
        'tax_amount'             => 'decimal:2',
        'is_inter_state'         => 'boolean',
        'cgst_amount'            => 'decimal:2',
        'sgst_amount'            => 'decimal:2',
        'igst_amount'            => 'decimal:2',
        'total'                  => 'decimal:2',
        'customer_responded_at'  => 'datetime',
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

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    // Root quotation this one was revised from (null if this IS the original).
    public function parentQuotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class, 'parent_quotation_id');
    }

    // Later versions revised from this quotation.
    public function revisions(): HasMany
    {
        return $this->hasMany(Quotation::class, 'parent_quotation_id')->orderByDesc('version');
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                     ->whereYear('created_at', now()->year);
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isPast()
            && !in_array($this->status, ['accepted', 'rejected']);
    }

    public function canConvert(): bool
    {
        return $this->status === 'accepted' && !$this->invoice;
    }

    public function getFormattedTotalAttribute(): string
    {
        return $this->currencySymbol() . number_format($this->total, 2);
    }

    public function currencySymbol(): string
    {
        return config("quotation.currencies.{$this->currency}.symbol", $this->currency ?? '₹');
    }

    // ── Public sharing / customer self-serve accept-reject ──────────

    public function ensurePublicToken(): string
    {
        if (!$this->public_token) {
            $this->update(['public_token' => \Illuminate\Support\Str::random(48)]);
        }

        return $this->public_token;
    }

    public function publicUrl(): string
    {
        return route('public.quotations.show', $this->ensurePublicToken());
    }

    public function hasCustomerResponded(): bool
    {
        return in_array($this->status, ['accepted', 'rejected']) && $this->customer_responded_at !== null;
    }

    // ── Auto generate number ──────────────────────────────────────
    public static function generateNumber(): string
    {
        $lastId = static::withoutGlobalScopes()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->max('id') ?? 0;

        $num = str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
        return 'QT-' . now()->format('Ymd') . '-' . $num;
    }

    // ── Calculate totals from items ───────────────────────────────
    public static function calculateTotals(array $items, float $discount = 0, float $taxPercent = 18): array
    {
        $subtotal  = 0;
        $taxAmount = 0;

        foreach ($items as $item) {
            $qty    = (float) ($item['quantity'] ?? 0);
            $rate   = (float) ($item['rate'] ?? 0);
            $rowAmt = $qty * $rate;
            $subtotal += $rowAmt;

            // Each product/line item carries its own GST rate — fall back to
            // the document-level rate only when a row doesn't specify one.
            $itemTax = isset($item['tax_percent']) && $item['tax_percent'] !== ''
                ? (float) $item['tax_percent']
                : $taxPercent;
            $taxAmount += $rowAmt * $itemTax / 100;
        }

        $discountedSubtotal = $subtotal - $discount;
        $discRatio = $subtotal > 0 ? $discountedSubtotal / $subtotal : 1;
        $taxAmount = round($taxAmount * $discRatio, 2);
        $total     = round($discountedSubtotal + $taxAmount, 2);

        // Blended effective tax % — for display only; actual tax is computed per item above.
        $effectiveTaxPct = $discountedSubtotal > 0
            ? round($taxAmount / $discountedSubtotal * 100, 2)
            : $taxPercent;

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
        return [
            'draft'    => 'Draft',
            'sent'     => 'Sent',
            'accepted' => 'Accepted',
            'rejected' => 'Rejected',
        ];
    }
}