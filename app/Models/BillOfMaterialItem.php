<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillOfMaterialItem extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'material_id',
        'quantity_per_unit',
    ];

    protected $casts = [
        'quantity_per_unit' => 'decimal:4',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // The finished good this BOM row belongs to.
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    // The raw material required.
    public function material(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'material_id');
    }
}
