<?php

namespace App\Exports;

use App\Http\Controllers\Web\Tenant\LeadController;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

// Exports exactly the rows the tenant.leads.index page would show for the
// same filters — reuses LeadController::filteredQuery() so the two never drift.
class LeadsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(
        private int $tenantId,
        private array $filters,
        private User $user,
    ) {}

    public function query()
    {
        return LeadController::filteredQuery($this->tenantId, $this->filters, $this->user)
            ->with('assignedTo')
            ->orderByDesc('created_at');
    }

    public function headings(): array
    {
        return [
            'Name', 'Phone', 'Email', 'Company', 'Designation', 'City', 'State',
            'Source', 'Status', 'Priority', 'Lead Value', 'Assigned To',
            'Expected Close Date', 'Notes', 'Created At',
        ];
    }

    public function map($lead): array
    {
        return [
            $lead->name,
            $lead->phone,
            $lead->email,
            $lead->company,
            $lead->designation,
            $lead->city,
            $lead->state,
            $lead->source,
            $lead->status,
            $lead->priority,
            $lead->lead_value,
            $lead->assignedTo?->name,
            optional($lead->expected_close_date)->format('Y-m-d'),
            $lead->notes,
            $lead->created_at->format('Y-m-d H:i'),
        ];
    }
}
