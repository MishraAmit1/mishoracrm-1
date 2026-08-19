<?php

namespace App\Models;

use App\BelongsToTenant;
use App\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use SoftDeletes, BelongsToTenant, HasAuditLog, HasFactory;

    protected $fillable = [
        'tenant_id',
        'vendor_id',
        'purchase_request_id',
        'number',
        'date',
        'expected_delivery_date',
        'items',
        'subtotal',
        'discount',
        'tax_percent',
        'tax_amount',
        'total',
        'notes',
        'terms',
        'status',
        'created_by',
    ];

    protected $casts = [
        'items'                   => 'array',
        'date'                    => 'date',
        'expected_delivery_date'  => 'date',
        'subtotal'                => 'decimal:2',
        'discount'                => 'decimal:2',
        'tax_percent'             => 'decimal:2',
        'tax_amount'              => 'decimal:2',
        'total'                   => 'decimal:2',
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

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function vendorQuotes(): HasMany
    {
        return $this->hasMany(VendorQuote::class)->latest();
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function isFullyReceived(): bool
    {
        $items = $this->items ?? [];

        if (empty($items)) {
            return false;
        }

        foreach ($items as $item) {
            $qty      = (float) ($item['quantity'] ?? 0);
            $received = (float) ($item['received_quantity'] ?? 0);
            if ($received < $qty) {
                return false;
            }
        }

        return true;
    }

    public function isPartiallyReceived(): bool
    {
        $anyReceived = false;

        foreach ($this->items ?? [] as $item) {
            if ((float) ($item['received_quantity'] ?? 0) > 0) {
                $anyReceived = true;
                break;
            }
        }

        return $anyReceived && !$this->isFullyReceived();
    }

    public function getFormattedTotalAttribute(): string
    {
        return '₹' . number_format($this->total, 2);
    }

    // ── Auto generate number ──────────────────────────────────────
    public static function generateNumber(): string
    {
        $lastId = static::withoutGlobalScopes()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->max('id') ?? 0;

        $num = str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
        return 'PO-' . now()->format('Ymd') . '-' . $num;
    }

    // ── Calculate totals from items (literal copy of Quotation's) ──
    public static function calculateTotals(array $items, float $discount = 0, float $taxPercent = 0): array
    {
        $subtotal  = 0;
        $taxAmount = 0;

        foreach ($items as $item) {
            $qty    = (float) ($item['quantity'] ?? 0);
            $rate   = (float) ($item['rate'] ?? 0);
            $rowAmt = $qty * $rate;
            $subtotal += $rowAmt;

            $itemTax = isset($item['tax_percent']) && $item['tax_percent'] !== ''
                ? (float) $item['tax_percent']
                : $taxPercent;
            $taxAmount += $rowAmt * $itemTax / 100;
        }

        $discountedSubtotal = $subtotal - $discount;
        $discRatio = $subtotal > 0 ? $discountedSubtotal / $subtotal : 1;
        $taxAmount = round($taxAmount * $discRatio, 2);
        $total     = round($discountedSubtotal + $taxAmount, 2);

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
            'draft'               => 'Draft',
            'sent'                => 'Sent',
            'partially_received'  => 'Partially Received',
            'received'            => 'Received',
            'cancelled'           => 'Cancelled',
        ];
    }
}
