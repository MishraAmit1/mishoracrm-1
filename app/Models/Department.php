<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function getStaffCountAttribute(): int
    {
        return $this->staff()->count();
    }
}