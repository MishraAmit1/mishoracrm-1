<?php

namespace App\Services\Import;

use App\Models\CustomFieldValue;
use App\Models\Lead;
use App\Models\TenantFieldAssignment;
use App\Models\User;
use App\Services\DuplicateMatcher;
use Carbon\Carbon;

class LeadImportService
{
    // Mapping-dropdown options for core Lead columns.
    public function fieldOptions(): array
    {
        return [
            'name'                 => 'Name *',
            'phone'                => 'Phone *',
            'email'                => 'Email',
            'company'              => 'Company',
            'designation'          => 'Designation',
            'city'                 => 'City',
            'state'                => 'State',
            'source'               => 'Source',
            'status'               => 'Status',
            'priority'             => 'Priority',
            'lead_value'           => 'Lead Value',
            'notes'                => 'Notes',
            'expected_close_date'  => 'Expected Close Date',
            'assigned_to'          => 'Assigned To (staff name)',
        ];
    }

    // Extra mapping options for the tenant's active custom fields on Lead.
    public function customFieldOptions(int $tenantId): array
    {
        return TenantFieldAssignment::getActiveFields($tenantId, 'lead')
            ->mapWithKeys(fn($f) => [
                'custom:' . $f['field_key'] => ($f['label'] ?? $f['field_key']) . ($f['is_required'] ? ' * (custom)' : ' (custom)'),
            ])
            ->toArray();
    }

    // $rows: array of plain arrays (data rows only, header already stripped).
    // $mapping: [columnIndex => 'name'|'phone'|...|'custom:{field_key}'].
    public function process(int $tenantId, array $rows, array $mapping): array
    {
        $created = 0;
        $skipped = 0;
        $errors  = [];

        $customFields = TenantFieldAssignment::getActiveFields($tenantId, 'lead')->keyBy('field_key');

        $staffByName = User::where('tenant_id', $tenantId)
            ->get(['id', 'name'])
            ->mapWithKeys(fn($u) => [strtolower($u->name) => $u->id]);

        foreach ($rows as $i => $row) {
            $rowNum = $i + 2; // +1 for header row, +1 for 1-based display

            $data       = [];
            $customData = [];

            foreach ($mapping as $colIndex => $field) {
                if (!$field) continue;
                $value = trim((string) ($row[$colIndex] ?? ''));
                if ($value === '') continue;

                if (str_starts_with($field, 'custom:')) {
                    $customData[substr($field, 7)] = $value;
                } else {
                    $data[$field] = $value;
                }
            }

            if (empty($data['name'])) {
                $errors[] = ['row' => $rowNum, 'message' => 'Name is required.'];
                continue;
            }
            if (empty($data['phone'])) {
                $errors[] = ['row' => $rowNum, 'message' => 'Phone is required.'];
                continue;
            }

            $phone = $data['phone'];
            $email = $data['email'] ?? null;

            if (DuplicateMatcher::findExistingLead($tenantId, $phone, $email)) {
                $skipped++;
                continue;
            }

            if (isset($data['source']) && !array_key_exists($data['source'], Lead::sources())) {
                unset($data['source']);
            }
            if (isset($data['status']) && !array_key_exists($data['status'], Lead::statuses())) {
                unset($data['status']);
            }
            if (isset($data['priority']) && !array_key_exists($data['priority'], Lead::priorities())) {
                unset($data['priority']);
            }
            if (isset($data['lead_value']) && !is_numeric($data['lead_value'])) {
                unset($data['lead_value']);
            }
            if (isset($data['expected_close_date'])) {
                try {
                    $data['expected_close_date'] = Carbon::parse($data['expected_close_date']);
                } catch (\Throwable) {
                    unset($data['expected_close_date']);
                }
            }

            $assignedTo = null;
            if (!empty($data['assigned_to'])) {
                $assignedTo = $staffByName->get(strtolower($data['assigned_to']));
            }
            unset($data['assigned_to']);

            try {
                $lead = Lead::create(array_merge($data, [
                    'tenant_id'   => $tenantId,
                    'created_by'  => auth()->id(),
                    'assigned_to' => $assignedTo,
                    'source'      => $data['source'] ?? 'other',
                    'status'      => $data['status'] ?? 'new',
                    'priority'    => $data['priority'] ?? 'medium',
                ]));

                foreach ($customData as $fieldKey => $value) {
                    $assignment = $customFields->get($fieldKey);
                    if (!$assignment) continue;

                    $fieldType = $assignment['field_type'] ?? 'text';
                    $coerced   = match ($fieldType) {
                        'multi_select' => json_encode(array_values(array_filter(array_map('trim', explode(';', $value))))),
                        'checkbox'     => in_array(strtolower($value), ['1', 'yes', 'true'], true) ? '1' : '0',
                        'number'       => is_numeric($value) ? $value : null,
                        default        => $value,
                    };

                    CustomFieldValue::updateOrCreate(
                        [
                            'tenant_id'  => $tenantId,
                            'model_type' => Lead::class,
                            'model_id'   => $lead->id,
                            'field_key'  => $fieldKey,
                        ],
                        [
                            'value'         => $coerced ?? '',
                            'assignment_id' => $assignment['id'],
                        ]
                    );
                }

                $created++;
            } catch (\Throwable $e) {
                $errors[] = ['row' => $rowNum, 'message' => $e->getMessage()];
            }
        }

        return compact('created', 'skipped', 'errors');
    }
}
