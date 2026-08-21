<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'service_code',
        'description',
        'rate',
        'tax_percent',
        'hsn',
        'unit',
        'is_active',
        'billing_cycle',
        'duration_value',
        'duration_unit',
        'total_quantity',
        'is_package',
        'is_bookable',
    ];

    protected $casts = [
        'rate'           => 'decimal:2',
        'tax_percent'    => 'decimal:2',
        'is_active'      => 'boolean',
        'duration_value' => 'integer',
        'total_quantity' => 'integer',
        'is_package'     => 'boolean',
        'is_bookable'    => 'boolean',
    ];

    // one_time | monthly | quarterly | yearly
    public static function billingCycles(): array
    {
        return [
            'one_time' => 'One-time',
            'monthly'  => 'Monthly',
            'quarterly'=> 'Quarterly',
            'yearly'   => 'Yearly',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(ServiceSubscription::class);
    }

    // The individual Services bundled inside this Package. Purely
    // informational/reference — invoicing, subscriptions, reminders, and
    // usage tracking all treat a Package exactly like any other Service,
    // driven by ITS OWN rate/tax/billing_cycle/duration/total_quantity.
    public function packageComponents(): BelongsToMany
    {
        return $this->belongsToMany(
            Service::class,
            'service_package_items',
            'package_service_id',
            'component_service_id'
        )->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePackages($query)
    {
        return $query->where('is_package', true);
    }

    public function scopeStandalone($query)
    {
        return $query->where('is_package', false);
    }

    public function scopeBookable($query)
    {
        return $query->where('is_bookable', true);
    }
}
