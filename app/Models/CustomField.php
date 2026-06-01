<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CustomField extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'module', 'label', 'field_key',
        'field_type', 'options', 'placeholder',
        'default_value', 'is_required', 'is_active',
        'show_in_list', 'show_in_filter', 'sort_order',
    ];

    protected $casts = [
        'options'         => 'array',
        'is_required'     => 'boolean',
        'is_active'       => 'boolean',
        'show_in_list'    => 'boolean',
        'show_in_filter'  => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function values(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class);
    }

    // ── Auto generate field_key from label ────────────────────────
    public static function generateKey(string $label): string
    {
        return Str::slug($label, '_');
    }

    // ── Available modules ─────────────────────────────────────────
    public static function modules(): array
    {
        return [
            'lead'       => ['label' => 'Leads',       'model' => \App\Models\Lead::class],
            'contact'    => ['label' => 'Contacts',    'model' => \App\Models\Contact::class],
            'deal'       => ['label' => 'Deals',       'model' => \App\Models\Deal::class],
            'quotation'  => ['label' => 'Quotations',  'model' => \App\Models\Quotation::class],
            'task'       => ['label' => 'Tasks',       'model' => \App\Models\Task::class],
        ];
    }

    // ── Field types ───────────────────────────────────────────────
    public static function fieldTypes(): array
    {
        return [
            'text'         => ['label' => 'Single Line Text',  'icon' => 'T'],
            'textarea'     => ['label' => 'Multi Line Text',   'icon' => '¶'],
            'number'       => ['label' => 'Number',            'icon' => '#'],
            'date'         => ['label' => 'Date',              'icon' => '📅'],
            'datetime'     => ['label' => 'Date & Time',       'icon' => '🕐'],
            'dropdown'     => ['label' => 'Dropdown',          'icon' => '▾'],
            'multi_select' => ['label' => 'Multi Select',      'icon' => '☑'],
            'checkbox'     => ['label' => 'Checkbox (Yes/No)', 'icon' => '✓'],
            'url'          => ['label' => 'URL',               'icon' => '🔗'],
            'email'        => ['label' => 'Email',             'icon' => '@'],
            'phone'        => ['label' => 'Phone',             'icon' => '📞'],
        ];
    }

    // ── Get fields for a module ───────────────────────────────────
    public static function forModule(string $module): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('tenant_id', auth()->user()->tenant_id)
            ->where('module', $module)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    // ── Get options as array ──────────────────────────────────────
    public function getOptionsArrayAttribute(): array
    {
        return $this->options ?? [];
    }
}