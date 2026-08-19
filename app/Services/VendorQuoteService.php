<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\VendorQuote;
use Illuminate\Validation\ValidationException;

class VendorQuoteService
{
    // ── Store — one vendor's quoted rates against the PO's existing
    // item lines (same product/qty, vendor supplies rate/tax_percent).
    // Only reachable while the PO is still a vendor-less draft. ──────
    public static function store(PurchaseOrder $purchaseOrder, array $data, int $userId): VendorQuote
    {
        $items = collect($data['items'])->map(fn ($item) => [
            'product_id'         => $item['product_id'] ?? null,
            'name'               => $item['name'],
            'description'        => $item['description'] ?? null,
            'quantity'           => (float) $item['quantity'],
            'rate'               => (float) ($item['rate'] ?? 0),
            'tax_percent'        => (float) ($item['tax_percent'] ?? 0),
            'amount'             => round((float) $item['quantity'] * (float) ($item['rate'] ?? 0), 2),
            'received_quantity'  => 0,
        ])->toArray();

        $totals = PurchaseOrder::calculateTotals($items, 0, 0);

        return VendorQuote::create([
            'tenant_id'          => $purchaseOrder->tenant_id,
            'purchase_order_id'  => $purchaseOrder->id,
            'vendor_id'          => $data['vendor_id'],
            'items'              => $items,
            'subtotal'           => $totals['subtotal'],
            'tax_amount'         => $totals['tax_amount'],
            'total'              => $totals['total'],
            'notes'              => $data['notes'] ?? null,
            'status'             => 'submitted',
            'created_by'         => $userId,
        ]);
    }

    // ── Select — copies the winning quote's rates onto the PO, sets its
    // vendor, recalculates totals, and marks the other quotes rejected. ──
    public static function select(VendorQuote $quote): PurchaseOrder
    {
        $purchaseOrder = $quote->purchaseOrder;

        if ($purchaseOrder->status !== 'draft') {
            throw ValidationException::withMessages([
                'quote' => 'Vendor can only be selected while the Purchase Order is still a draft.',
            ]);
        }

        $totals = PurchaseOrder::calculateTotals($quote->items, 0, 0);

        $purchaseOrder->update(array_merge($totals, [
            'vendor_id' => $quote->vendor_id,
            'items'     => $quote->items,
            'discount'  => 0,
        ]));

        $quote->update(['status' => 'selected']);

        VendorQuote::where('purchase_order_id', $purchaseOrder->id)
            ->where('id', '!=', $quote->id)
            ->update(['status' => 'rejected']);

        return $purchaseOrder;
    }

    public static function destroy(VendorQuote $quote): void
    {
        $quote->delete();
    }
}
