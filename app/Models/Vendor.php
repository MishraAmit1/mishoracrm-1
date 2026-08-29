<?php

namespace App\Models;

use App\BelongsToTenant;
use App\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use SoftDeletes, BelongsToTenant, HasAuditLog, HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'company',
        'phone',
        'email',
        'gst_number',
        'payment_terms_days',
        'address',
        'city',
        'state',
        'pincode',
        'notes',
        'bank_details',
    ];

    protected $casts = [
        'payment_terms_days' => 'integer',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class)->latest();
    }

    public function bills(): HasMany
    {
        return $this->hasMany(VendorBill::class)->latest();
    }

    // Total still owed to this vendor across all unpaid/partially-paid bills.
    public function outstandingAmount(): float
    {
        return round((float) VendorBill::withoutGlobalScopes()
            ->where('tenant_id', $this->tenant_id)
            ->where('vendor_id', $this->getKey())
            ->outstanding()
            ->sum(\Illuminate\Support\Facades\DB::raw('total - amount_paid')), 2);
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name',    'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('company', 'like', "%{$search}%");
        });
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function getFullAddressAttribute(): string
    {
        return collect([
            $this->address,
            $this->city,
            $this->state,
            $this->pincode,
        ])->filter()->join(', ');
    }
}
