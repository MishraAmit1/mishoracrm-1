<?php

namespace App;

use App\Models\CustomField;
use App\Models\CustomFieldValue;

trait HasCustomFields
{
    // ── Get all custom field values for this model ────────────────
    public function customFieldValues()
    {
        return $this->morphMany(CustomFieldValue::class, 'model');
    }

    // ── Get values as key=>value array ────────────────────────────
    public function getCustomDataAttribute(): array
    {
        return CustomFieldValue::getForModel($this);
    }

    // ── Get value for a specific field_key ────────────────────────
    public function getCustomValue(string $fieldKey): mixed
    {
        return $this->customFieldValues()
            ->whereHas('customField', fn($q) => $q->where('field_key', $fieldKey))
            ->first()
            ?->value;
    }

    // ── Save custom fields ────────────────────────────────────────
    public function saveCustomFields(array $data, int $tenantId): void
    {
        CustomFieldValue::saveForModel($this, $data, $tenantId);
    }

    // ── Get fields definition for this module ─────────────────────
    public function getCustomFieldsAttribute()
    {
        return CustomField::forModule(static::$customFieldModule ?? '');
    }
}