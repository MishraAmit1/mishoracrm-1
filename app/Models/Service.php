<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
    ];

    protected $casts = [
        'rate'           => 'decimal:2',
        'tax_percent'    => 'decimal:2',
        'is_active'      => 'boolean',
        'duration_value' => 'integer',
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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
