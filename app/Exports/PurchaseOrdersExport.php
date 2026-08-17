<?php

namespace App\Exports;

use App\Http\Controllers\Web\Tenant\PurchaseOrderController;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

// Exports exactly the rows the tenant.purchase-orders.index page would show
// for the same filters — reuses PurchaseOrderController::filteredQuery() so
// the two never drift.
class PurchaseOrdersExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(
        private int $tenantId,
        private array $filters,
        private User $user,
    ) {}

    public function query()
    {
        return PurchaseOrderController::filteredQuery($this->tenantId, $this->filters, $this->user)
            ->with(['vendor', 'purchaseRequest', 'createdBy'])
            ->orderByDesc('created_at');
    }

    public function headings(): array
    {
        return [
            'Number', 'Date', 'Expected Delivery', 'Vendor', 'Linked PR',
            'Subtotal', 'Discount', 'Tax %', 'Tax Amount', 'Total',
            'Status', 'Created By', 'Created At',
        ];
    }

    public function map($purchaseOrder): array
    {
        return [
            $purchaseOrder->number,
            optional($purchaseOrder->date)->format('Y-m-d'),
            optional($purchaseOrder->expected_delivery_date)->format('Y-m-d'),
            $purchaseOrder->vendor?->name,
            $purchaseOrder->purchaseRequest?->number,
            $purchaseOrder->subtotal,
            $purchaseOrder->discount,
            $purchaseOrder->tax_percent,
            $purchaseOrder->tax_amount,
            $purchaseOrder->total,
            $purchaseOrder->status,
            $purchaseOrder->createdBy?->name,
            $purchaseOrder->created_at->format('Y-m-d H:i'),
        ];
    }
}
