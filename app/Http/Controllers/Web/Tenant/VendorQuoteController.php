<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\VendorQuoteRequest;
use App\Models\PurchaseOrder;
use App\Models\VendorQuote;
use App\Services\VendorQuoteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class VendorQuoteController extends Controller
{
    private function findPurchaseOrder(int|string $id): PurchaseOrder
    {
        return PurchaseOrder::where('id', $id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->firstOrFail();
    }

    private function findQuote(PurchaseOrder $purchaseOrder, int|string $quoteId): VendorQuote
    {
        return VendorQuote::where('id', $quoteId)
            ->where('purchase_order_id', $purchaseOrder->id)
            ->firstOrFail();
    }

    // ── Store — add a vendor's quote against the draft PO's items ────
    public function store(VendorQuoteRequest $request, int|string $id): RedirectResponse
    {
        $purchaseOrder = $this->findPurchaseOrder($id);
        $this->authorize('modify', $purchaseOrder);

        if ($purchaseOrder->status !== 'draft') {
            return back()->with('error', 'Vendor quotes can only be added to a draft purchase order.');
        }

        VendorQuoteService::store($purchaseOrder, $request->validated(), auth()->id());

        return redirect()
            ->route('tenant.purchase-orders.show', $purchaseOrder->id)
            ->with('success', 'Vendor quote added.');
    }

    // ── Select — apply this quote's vendor + rates onto the PO ───────
    public function select(int|string $id, int|string $quoteId): RedirectResponse
    {
        $purchaseOrder = $this->findPurchaseOrder($id);
        $this->authorize('modify', $purchaseOrder);
        $quote = $this->findQuote($purchaseOrder, $quoteId);

        try {
            VendorQuoteService::select($quote);
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()
            ->route('tenant.purchase-orders.show', $purchaseOrder->id)
            ->with('success', 'Vendor selected — Purchase Order updated with their rates.');
    }

    // ── Destroy ───────────────────────────────────────────────────
    public function destroy(int|string $id, int|string $quoteId): RedirectResponse
    {
        $purchaseOrder = $this->findPurchaseOrder($id);
        $this->authorize('modify', $purchaseOrder);
        $quote = $this->findQuote($purchaseOrder, $quoteId);

        VendorQuoteService::destroy($quote);

        return redirect()
            ->route('tenant.purchase-orders.show', $purchaseOrder->id)
            ->with('success', 'Vendor quote removed.');
    }
}
