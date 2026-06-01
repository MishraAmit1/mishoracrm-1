<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GlobalFieldTemplate extends Model
{
    protected $fillable = [
        'module', 'label', 'field_key', 'field_type',
        'options', 'placeholder', 'description',
        'is_system', 'is_recommended', 'sort_order',
    ];

    protected $casts = [
        'options'        => 'array',
        'is_system'      => 'boolean',
        'is_recommended' => 'boolean',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(TenantFieldAssignment::class, 'global_template_id');
    }

    // Get templates for a module
    public static function forModule(string $module)
    {
        return static::where('module', $module)
            ->orderBy('sort_order')
            ->get();
    }

    // Get templates NOT yet assigned to a tenant
    public static function unassignedForTenant(int $tenantId, string $module)
    {
        $assignedIds = TenantFieldAssignment::where('tenant_id', $tenantId)
            ->where('module', $module)
            ->whereNotNull('global_template_id')
            ->pluck('global_template_id');

        return static::where('module', $module)
            ->whereNotIn('id', $assignedIds)
            ->orderBy('sort_order')
            ->get();
    }
}