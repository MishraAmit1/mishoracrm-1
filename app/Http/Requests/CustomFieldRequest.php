<?php

namespace App\Http\Requests;

use App\Models\TenantFieldAssignment;
use Illuminate\Foundation\Http\FormRequest;

class CustomFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // ── Dynamic rules from TenantFieldAssignment ──────────────────
    public function rules(): array
    {
        $rules = [];

        $fields = TenantFieldAssignment::getActiveFields(
            auth()->user()->tenant_id,
            $this->getModule()
        );

        foreach ($fields as $field) {
            $key   = "custom_fields.{$field['id']}";
            $type  = $field['field_type'] ?? 'text';
            $req   = $field['is_required'] ?? false;

            $fieldRules = $req ? ['required'] : ['nullable'];

            // Type-specific rules
            $fieldRules = array_merge($fieldRules, match($type) {
                'email'        => ['email'],
                'url'          => ['url'],
                'number'       => ['numeric'],
                'date'         => ['date'],
                'datetime'     => ['date'],
                'checkbox'     => ['boolean'],
                'dropdown'     => $this->inRule($field['options'] ?? []),
                'multi_select' => ['array'],
                'text',
                'textarea',
                'phone'        => ['string', 'max:500'],
                default        => ['string'],
            });

            $rules[$key] = $fieldRules;

            // Multi select items validation
            if ($type === 'multi_select' && !empty($field['options'])) {
                $rules["{$key}.*"] = ['string', 'in:' . implode(',', $field['options'])];
            }
        }

        return $rules;
    }

    // ── Custom messages ───────────────────────────────────────────
    public function messages(): array
    {
        $messages = [];

        $fields = TenantFieldAssignment::getActiveFields(
            auth()->user()->tenant_id,
            $this->getModule()
        );

        foreach ($fields as $field) {
            $key   = "custom_fields.{$field['id']}";
            $label = $field['label'] ?? 'This field';

            $messages["{$key}.required"] = "{$label} is required.";
            $messages["{$key}.email"]    = "{$label} must be a valid email.";
            $messages["{$key}.url"]      = "{$label} must be a valid URL.";
            $messages["{$key}.numeric"]  = "{$label} must be a number.";
            $messages["{$key}.date"]     = "{$label} must be a valid date.";
            $messages["{$key}.in"]       = "{$label} must be one of the allowed options.";
        }

        return $messages;
    }

    // ── Custom attribute names ────────────────────────────────────
    public function attributes(): array
    {
        $attributes = [];

        $fields = TenantFieldAssignment::getActiveFields(
            auth()->user()->tenant_id,
            $this->getModule()
        );

        foreach ($fields as $field) {
            $attributes["custom_fields.{$field['id']}"] = $field['label'] ?? 'Field';
        }

        return $attributes;
    }

    // ── Get module from route ─────────────────────────────────────
    private function getModule(): string
    {
        // Route pe module name se detect karo
        // e.g. leads -> lead, contacts -> contact, deals -> deal
        $routeName = $this->route()?->getName() ?? '';

        return match(true) {
            str_contains($routeName, 'lead')      => 'lead',
            str_contains($routeName, 'contact')   => 'contact',
            str_contains($routeName, 'deal')      => 'deal',
            str_contains($routeName, 'quotation') => 'quotation',
            str_contains($routeName, 'task')      => 'task',
            default                               => 'lead',
        };
    }

    // ── Helper: in rule only if options exist ─────────────────────
    private function inRule(array $options): array
    {
        if (empty($options)) return ['string'];
        return ['in:' . implode(',', $options)];
    }
}