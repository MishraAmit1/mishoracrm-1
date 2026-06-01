<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantFieldAssignment extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'module',
        'global_template_id', 'custom_field_id',
        'is_active', 'is_required',
        'show_in_list', 'show_in_filter',
        'sort_order',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'is_required'    => 'boolean',
        'show_in_list'   => 'boolean',
        'show_in_filter' => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function globalTemplate(): BelongsTo
    {
        return $this->belongsTo(GlobalFieldTemplate::class, 'global_template_id');
    }

    public function customField(): BelongsTo
    {
        return $this->belongsTo(CustomField::class, 'custom_field_id');
    }

    // ── Get merged field info ─────────────────────────────────────
    // Returns unified field data regardless of source
    public function getFieldInfoAttribute(): array
    {
        $source = $this->global_template_id
            ? $this->globalTemplate
            : $this->customField;

        if (!$source) return [];

        return [
            'id'          => $this->id,
            'label'       => $source->label,
            'field_key'   => $source->field_key,
            'field_type'  => $source->field_type,
            'options'     => $source->options ?? [],
            'placeholder' => $source->placeholder,
            'is_required' => $this->is_required,
            'is_active'   => $this->is_active,
            'show_in_list'=> $this->show_in_list,
            'show_in_filter'=> $this->show_in_filter,
            'source'      => $this->global_template_id ? 'global' : 'custom',
            'is_system'   => $this->globalTemplate?->is_system ?? false,
        ];
    }

    // ── Get all active fields for a tenant+module ─────────────────
    public static function getActiveFields(int $tenantId, string $module): \Illuminate\Support\Collection
    {
        return static::where('tenant_id', $tenantId)
            ->where('module', $module)
            ->where('is_active', true)
            ->with(['globalTemplate', 'customField'])
            ->orderBy('sort_order')
            ->get()
            ->map(fn($a) => $a->field_info)
            ->filter();
    }

    // ── Assign global template to tenant ─────────────────────────
    public static function assignGlobal(
        int $tenantId,
        string $module,
        int $templateId,
        array $options = []
    ): self {
        $maxOrder = static::where('tenant_id', $tenantId)
            ->where('module', $module)
            ->max('sort_order') ?? 0;

        return static::create([
            'tenant_id'          => $tenantId,
            'module'             => $module,
            'global_template_id' => $templateId,
            'custom_field_id'    => null,
            'is_active'          => $options['is_active']   ?? true,
            'is_required'        => $options['is_required']  ?? false,
            'show_in_list'       => $options['show_in_list'] ?? false,
            'show_in_filter'     => $options['show_in_filter'] ?? false,
            'sort_order'         => $maxOrder + 1,
        ]);
    }

    // ── Assign custom field to tenant module ──────────────────────
    public static function assignCustom(int $tenantId, string $module, int $customFieldId): self
    {
        $maxOrder = static::where('tenant_id', $tenantId)
            ->where('module', $module)
            ->max('sort_order') ?? 0;

        return static::firstOrCreate(
            [
                'tenant_id'       => $tenantId,
                'module'          => $module,
                'custom_field_id' => $customFieldId,
            ],
            ['sort_order' => $maxOrder + 1, 'is_active' => true]
        );
    }
}