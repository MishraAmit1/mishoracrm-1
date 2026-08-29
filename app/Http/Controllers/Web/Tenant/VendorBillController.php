<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Helpers\ViewScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\VendorBillRequest;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorBill;
use App\Services\VendorBillService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VendorBillController extends Controller
{
    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    private function findBill(int|string $id): VendorBill
    {
        return VendorBill::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();
    }

    public static function filteredQuery(int $tenantId, array $filters, User $user): \Illuminate\Database\Eloquent\Builder
    {
        $query = VendorBill::query()->where('tenant_id', $tenantId);
        $query = ViewScope::apply($query, 'vendor_bills', $user, 'created_by');

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'overdue') {
                $query->overdue();
            } else {
                $query->status($filters['status']);
            }
        }

        if (!empty($filters['vendor_id'])) {
            $query->where('vendor_id', $filters['vendor_id']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('number', 'like', "%{$filters['search']}%")
                    ->orWhere('vendor_invoice_number', 'like', "%{$filters['search']}%")
                    ->orWhereHas('vendor', fn ($v) => $v->where('name', 'like', "%{$filters['search']}%"));
            });
        }

        return $query;
    }

    // ── Index ─────────────────────────────────────────────────────
    public function index(Request $request): View
    {
        $query = self::filteredQuery($this->tenantId(), $request->all(), auth()->user())
            ->with(['vendor', 'createdBy'])
            ->latest();

        $bills = $query->paginate(15)->withQueryString();

        $scoped = ViewScope::apply(VendorBill::where('tenant_id', $this->tenantId()), 'vendor_bills', auth()->user(), 'created_by');

        $summary = (clone $scoped)->selectRaw('status, COUNT(*) as count')->groupBy('status')->pluck('count', 'status');

        $counts = [
            'all'            => $summary->sum(),
            'unpaid'         => $summary->get('unpaid', 0),
            'partially_paid' => $summary->get('partially_paid', 0),
            'paid'           => $summary->get('paid', 0),
            'cancelled'      => $summary->get('cancelled', 0),
            'overdue'        => (clone $scoped)->overdue()->count(),
        ];

        $outstanding = (float) (clone $scoped)->outstanding()
            ->sum(DB::raw('total - amount_paid'));

        $vendors  = Vendor::where('tenant_id', $this->tenantId())->orderBy('name')->get(['id', 'name']);
        $statuses = VendorBill::statuses();

        return view('tenant.vendor-bills.index', compact('bills', 'counts', 'outstanding', 'vendors', 'statuses'));
    }

    // ── Create ────────────────────────────────────────────────────
    public function create(Request $request): View
    {
        $this->authorize('create', VendorBill::class);

        $vendors  = Vendor::where('tenant_id', $this->tenantId())->orderBy('name')->get(['id', 'name', 'company', 'gst_number', 'payment_terms_days']);
        $products = Product::where('tenant_id', $this->tenantId())->active()->orderBy('name')->get(['id', 'product_code', 'name', 'description', 'rate', 'cost_price', 'tax_percent', 'hsn', 'unit']);

        $prefill = null;
        $sourcePo = null;

        if ($request->filled('purchase_order_id')) {
            $sourcePo = PurchaseOrder::where('id', $request->purchase_order_id)
                ->where('tenant_id', $this->tenantId())
                ->first();

            if ($sourcePo) {
                $prefill = VendorBillService::draftFromPurchaseOrder($sourcePo);
            }
        }

        $number = VendorBill::generateNumber($this->tenantId());

        return view('tenant.vendor-bills.create', compact('vendors', 'products', 'prefill', 'sourcePo', 'number'));
    }

    // ── Store ─────────────────────────────────────────────────────
    public function store(VendorBillRequest $request): RedirectResponse
    {
        $this->authorize('create', VendorBill::class);

        $bill = VendorBillService::store($request->validated(), $this->tenantId(), auth()->id());

        return redirect()
            ->route('tenant.vendor-bills.show', $bill->id)
            ->with('success', "Vendor Bill {$bill->number} created.");
    }

    // ── Show ──────────────────────────────────────────────────────
    public function show(int|string $id): View
    {
        $bill = $this->findBill($id);
        $this->authorize('view', $bill);
        $bill->load(['vendor', 'purchaseOrder', 'createdBy', 'payments.recordedBy']);

        return view('tenant.vendor-bills.show', compact('bill'));
    }

    // ── Edit ──────────────────────────────────────────────────────
    public function edit(int|string $id): View|RedirectResponse
    {
        $bill = $this->findBill($id);
        $this->authorize('modify', $bill);

        if (!$bill->isEditable()) {
            return redirect()
                ->route('tenant.vendor-bills.show', $bill->id)
                ->with('error', 'A bill with payments recorded (or cancelled) cannot be edited.');
        }

        $vendors  = Vendor::where('tenant_id', $this->tenantId())->orderBy('name')->get(['id', 'name', 'company', 'gst_number', 'payment_terms_days']);
        $products = Product::where('tenant_id', $this->tenantId())->active()->orderBy('name')->get(['id', 'product_code', 'name', 'description', 'rate', 'cost_price', 'tax_percent', 'hsn', 'unit']);

        return view('tenant.vendor-bills.edit', compact('bill', 'vendors', 'products'));
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(VendorBillRequest $request, int|string $id): RedirectResponse
    {
        $bill = $this->findBill($id);
        $this->authorize('modify', $bill);

        if (!$bill->isEditable()) {
            return redirect()
                ->route('tenant.vendor-bills.show', $bill->id)
                ->with('error', 'A bill with payments recorded (or cancelled) cannot be edited.');
        }

        VendorBillService::update($bill, $request->validated());

        return redirect()
            ->route('tenant.vendor-bills.show', $bill->id)
            ->with('success', 'Vendor Bill updated.');
    }

    // ── Destroy ───────────────────────────────────────────────────
    public function destroy(int|string $id): RedirectResponse
    {
        $bill = $this->findBill($id);
        $this->authorize('delete', $bill);
        $number = $bill->number;
        $bill->delete();

        return redirect()
            ->route('tenant.vendor-bills.index')
            ->with('success', "Vendor Bill {$number} deleted.");
    }

    // ── Cancel ────────────────────────────────────────────────────
    public function cancel(int|string $id): RedirectResponse
    {
        $bill = $this->findBill($id);
        $this->authorize('modify', $bill);

        if ($bill->isPaid()) {
            return back()->with('error', 'A fully paid bill cannot be cancelled.');
        }

        VendorBillService::cancel($bill);

        return back()->with('success', 'Vendor Bill cancelled.');
    }

    // ── Record payment(s) ─────────────────────────────────────────
    public function recordPayment(Request $request, int|string $id): RedirectResponse
    {
        $bill = $this->findBill($id);
        $this->authorize('recordPayment', $bill);

        $request->validate([
            'payments'              => ['required', 'array', 'min:1'],
            'payments.*.amount'     => ['required', 'numeric', 'min:0.01'],
            'payments.*.method'     => ['required', 'in:' . implode(',', array_keys(VendorBill::paymentMethods()))],
            'payments.*.paid_at'    => ['required', 'date'],
            'payments.*.reference'  => ['nullable', 'string', 'max:255'],
            'payments.*.note'       => ['nullable', 'string', 'max:255'],
        ]);

        $rows  = $request->payments;
        $total = collect($rows)->sum('amount');

        if (round($total, 2) > round($bill->due_amount, 2) + 0.01) {
            return back()->with('error', 'Total payment exceeds the amount still due.')->withInput();
        }

        VendorBillService::recordPayments($bill, $rows, auth()->id());

        $count = count($rows);
        return back()->with('success', "{$count} payment(s) recorded. Status: " . (VendorBill::statuses()[$bill->fresh()->status] ?? ''));
    }
}
