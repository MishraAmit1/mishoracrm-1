<?php

namespace App\Exports;

use App\Http\Controllers\Web\Tenant\QuotationController;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

// Exports exactly the rows the tenant.quotations.index page would show for the
// same filters — reuses QuotationController::filteredQuery() so the two never drift.
class QuotationsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(
        private int $tenantId,
        private array $filters,
        private User $user,
    ) {}

    public function query()
    {
        return QuotationController::filteredQuery($this->tenantId, $this->filters, $this->user)
            ->with(['contact', 'lead', 'createdBy'])
            ->orderByDesc('created_at');
    }

    public function headings(): array
    {
        return [
            'Number', 'Date', 'Valid Until', 'Contact', 'Lead', 'Version',
            'Subtotal', 'Discount', 'Tax %', 'Tax Amount', 'Total', 'Currency',
            'Status', 'Created By', 'Created At',
        ];
    }

    public function map($quotation): array
    {
        return [
            $quotation->number,
            optional($quotation->date)->format('Y-m-d'),
            optional($quotation->valid_until)->format('Y-m-d'),
            $quotation->contact?->name,
            $quotation->lead?->name,
            $quotation->version,
            $quotation->subtotal,
            $quotation->discount,
            $quotation->tax_percent,
            $quotation->tax_amount,
            $quotation->total,
            $quotation->currency,
            $quotation->status,
            $quotation->createdBy?->name,
            $quotation->created_at->format('Y-m-d H:i'),
        ];
    }
}
