<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomFieldValue extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'model_type',
        'model_id',
        'field_key',        // stable unique key — e.g. 'budget_range'
        'assignment_id',    // TenantFieldAssignment id — edit prefill ke liye
        'custom_field_id',  // CustomField id — direct custom fields ke liye
        'value',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(TenantFieldAssignment::class, 'assignment_id');
    }

    public function customField(): BelongsTo
    {
        return $this->belongsTo(CustomField::class, 'custom_field_id');
    }

    // ─────────────────────────────────────────────────────────────
    // Edit form ke liye → assignment_id => value
    // custom_fields[{assignment_id}] = value  (prefill)
    // ─────────────────────────────────────────────────────────────
    public static function getByAssignmentForModel(Model $model): array
    {
        return static::where('model_type', get_class($model))
            ->where('model_id', $model->id)
            ->whereNotNull('assignment_id')
            ->get()
            ->mapWithKeys(fn($v) => [$v->assignment_id => $v->value])
            ->toArray();
    }

    // ─────────────────────────────────────────────────────────────
    // Show page ke liye → field_key => value
    // ─────────────────────────────────────────────────────────────
    public static function getByKeyForModel(Model $model): array
    {
        return static::where('model_type', get_class($model))
            ->where('model_id', $model->id)
            ->get()
            ->mapWithKeys(fn($v) => [$v->field_key => $v->value])
            ->toArray();
    }

    // Backward compat
    public static function getByIdForModel(Model $model): array
    {
        return static::getByAssignmentForModel($model);
    }

    // ── Display formatted value ───────────────────────────────────
    public function getDisplayValueAttribute(): string
    {
        $fieldType = $this->assignment?->field_info['field_type'] ?? 'text';

        return match($fieldType) {
            'checkbox'     => $this->value == '1' ? 'Yes' : 'No',
            'multi_select' => implode(', ', json_decode($this->value ?? '[]', true) ?: []),
            'date'         => $this->value
                                ? \Carbon\Carbon::parse($this->value)->format('d M Y')
                                : '—',
            'datetime'     => $this->value
                                ? \Carbon\Carbon::parse($this->value)->format('d M Y, h:i A')
                                : '—',
            default        => $this->value ?? '—',
        };
    }
}