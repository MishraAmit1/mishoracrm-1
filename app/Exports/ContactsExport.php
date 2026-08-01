<?php

namespace App\Exports;

use App\Http\Controllers\Web\Tenant\ContactController;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

// Exports exactly the rows the tenant.contacts.index page would show for the
// same filters — reuses ContactController::filteredQuery() so they never drift.
class ContactsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(
        private int $tenantId,
        private array $filters,
    ) {}

    public function query()
    {
        return ContactController::filteredQuery($this->tenantId, $this->filters)
            ->orderByDesc('created_at');
    }

    public function headings(): array
    {
        return [
            'Name', 'Phone', 'Email', 'Company', 'Designation', 'GST Number',
            'Address', 'City', 'State', 'Pincode', 'Notes', 'Created At',
        ];
    }

    public function map($contact): array
    {
        return [
            $contact->name,
            $contact->phone,
            $contact->email,
            $contact->company,
            $contact->designation,
            $contact->gst_number,
            $contact->address,
            $contact->city,
            $contact->state,
            $contact->pincode,
            $contact->notes,
            $contact->created_at->format('Y-m-d H:i'),
        ];
    }
}
