<?php

namespace App\Exports;

use App\Http\Controllers\Web\Tenant\DealController;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

// Exports exactly the rows the tenant.deals.index page would show for the
// same filters — reuses DealController::filteredQuery() so the two never drift.
class DealsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(
        private int $tenantId,
        private array $filters,
        private User $user,
    ) {}

    public function query()
    {
        return DealController::filteredQuery($this->tenantId, $this->filters, $this->user)
            ->with(['contact', 'assignedTo'])
            ->orderByDesc('created_at');
    }

    public function headings(): array
    {
        return [
            'Title', 'Value', 'Stage', 'Probability', 'Contact', 'Assigned To',
            'Expected Close Date', 'Actual Close Date', 'Notes', 'Created At',
        ];
    }

    public function map($deal): array
    {
        return [
            $deal->title,
            $deal->value,
            $deal->stage,
            $deal->probability,
            $deal->contact?->name,
            $deal->assignedTo?->name,
            optional($deal->expected_close_date)->format('Y-m-d'),
            optional($deal->actual_close_date)->format('Y-m-d'),
            $deal->notes,
            $deal->created_at->format('Y-m-d H:i'),
        ];
    }
}
