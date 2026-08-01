<?php

namespace App\Services\Import;

use App\Models\Contact;
use App\Services\DuplicateMatcher;

class ContactImportService
{
    public function fieldOptions(): array
    {
        return [
            'name'        => 'Name *',
            'phone'       => 'Phone *',
            'email'       => 'Email',
            'company'     => 'Company',
            'designation' => 'Designation',
            'gst_number'  => 'GST Number',
            'address'     => 'Address',
            'city'        => 'City',
            'state'       => 'State',
            'pincode'     => 'Pincode',
            'notes'       => 'Notes',
        ];
    }

    // $rows: array of plain arrays (data rows only, header already stripped).
    // $mapping: [columnIndex => 'name'|'phone'|...].
    public function process(int $tenantId, array $rows, array $mapping): array
    {
        $created = 0;
        $skipped = 0;
        $errors  = [];

        foreach ($rows as $i => $row) {
            $rowNum = $i + 2;

            $data = [];
            foreach ($mapping as $colIndex => $field) {
                if (!$field) continue;
                $value = trim((string) ($row[$colIndex] ?? ''));
                if ($value === '') continue;
                $data[$field] = $value;
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

            if (DuplicateMatcher::findExistingContact($tenantId, $phone, $email)) {
                $skipped++;
                continue;
            }

            try {
                Contact::create(array_merge($data, [
                    'tenant_id' => $tenantId,
                ]));
                $created++;
            } catch (\Throwable $e) {
                $errors[] = ['row' => $rowNum, 'message' => $e->getMessage()];
            }
        }

        return compact('created', 'skipped', 'errors');
    }
}
