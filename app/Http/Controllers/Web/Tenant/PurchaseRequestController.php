<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Helpers\ViewScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseRequestRequest;
use App\Models\Department;
use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Services\PurchaseRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseRequestController extends Controller
{
    // ── Find purchase request — tenant scope ────────────────────────
    private function findPurchaseRequest(int|string $id): PurchaseRequest
    {
        return PurchaseRequest::where('id', $id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->firstOrFail();
    }

    // ── Shared filtered query (index page reuses this) ──────────────
    public static function filteredQuery(int $tenantId, array $filters, User $user): \Illuminate\Database\Eloquent\Builder
    {
        $query = PurchaseRequest::query()->where('tenant_id', $tenantId);
        $query = ViewScope::apply($query, 'purchase_requests', $user, 'requested_by');

        if (!empty($filters['status'])) {
            $query->status($filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->where('number', 'like', "%{$filters['search']}%");
        }

        return $query;
    }

    // ── Index ─────────────────────────────────────────────────────
    public function index(Request $request): View
    {
        $query = self::filteredQuery(auth()->user()->tenant_id, $request->all(), auth()->user())
            ->with(['department', 'requestedBy', 'purchaseOrder'])
            ->latest();

        $purchaseRequests = $query->paginate(15)->withQueryString();

        $summary = ViewScope::apply(PurchaseRequest::where('tenant_id', auth()->user()->tenant_id), 'purchase_requests', auth()->user(), 'requested_by')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $counts = [
            'all'       => $summary->sum(),
            'pending'   => $summary->get('pending', 0),
            'approved'  => $summary->get('approved', 0),
            'rejected'  => $summary->get('rejected', 0),
            'converted' => $summary->get('converted', 0),
        ];

        $statuses = PurchaseRequest::statuses();

        return view('tenant.purchase-requests.index', compact('purchaseRequests', 'counts', 'statuses'));
    }

    // ── Create ────────────────────────────────────────────────────
    public function create(): View
    {
        $this->authorize('create', PurchaseRequest::class);

        $departments = Department::where('tenant_id', auth()->user()->tenant_id)->orderBy('name')->get(['id', 'name']);
        $products    = Product::where('tenant_id', auth()->user()->tenant_id)->active()->orderBy('name')->get(['id', 'product_code', 'name', 'description', 'unit']);
        $number      = PurchaseRequest::generateNumber();

        return view('tenant.purchase-requests.create', compact('departments', 'products', 'number'));
    }

    // ── Store ─────────────────────────────────────────────────────
    public function store(PurchaseRequestRequest $request): RedirectResponse
    {
        $this->authorize('create', PurchaseRequest::class);

        $purchaseRequest = PurchaseRequestService::store($request->validated(), auth()->user()->tenant_id, auth()->id());

        return redirect()
            ->route('tenant.purchase-requests.show', $purchaseRequest->id)
            ->with('success', "Purchase Request {$purchaseRequest->number} created successfully.");
    }

    // ── Show ──────────────────────────────────────────────────────
    public function show(int|string $id): View
    {
        $purchaseRequest = $this->findPurchaseRequest($id);
        $this->authorize('view', $purchaseRequest);
        $purchaseRequest->load(['department', 'requestedBy', 'approvedBy', 'purchaseOrder']);

        return view('tenant.purchase-requests.show', compact('purchaseRequest'));
    }

    // ── Edit ──────────────────────────────────────────────────────
    public function edit(int|string $id)
    {
        $purchaseRequest = $this->findPurchaseRequest($id);
        $this->authorize('modify', $purchaseRequest);

        $departments = Department::where('tenant_id', auth()->user()->tenant_id)->orderBy('name')->get(['id', 'name']);
        $products    = Product::where('tenant_id', auth()->user()->tenant_id)->active()->orderBy('name')->get(['id', 'product_code', 'name', 'description', 'unit']);

        return view('tenant.purchase-requests.edit', compact('purchaseRequest', 'departments', 'products'));
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(PurchaseRequestRequest $request, int|string $id): RedirectResponse
    {
        $purchaseRequest = $this->findPurchaseRequest($id);
        $this->authorize('modify', $purchaseRequest);

        PurchaseRequestService::update($purchaseRequest, $request->validated());

        return redirect()
            ->route('tenant.purchase-requests.show', $purchaseRequest->id)
            ->with('success', 'Purchase Request updated successfully.');
    }

    // ── Destroy ───────────────────────────────────────────────────
    public function destroy(int|string $id): RedirectResponse
    {
        $purchaseRequest = $this->findPurchaseRequest($id);
        $this->authorize('delete', $purchaseRequest);
        $number = $purchaseRequest->number;
        $purchaseRequest->delete();

        return redirect()
            ->route('tenant.purchase-requests.index')
            ->with('success', "Purchase Request {$number} deleted.");
    }

    // ── Approve — auto-creates a linked draft Purchase Order ────────
    public function approve(int|string $id): RedirectResponse
    {
        $purchaseRequest = $this->findPurchaseRequest($id);
        $this->authorize('approve', $purchaseRequest);

        $purchaseOrder = PurchaseRequestService::approve($purchaseRequest, auth()->id());

        $message = $purchaseOrder
            ? "Purchase Request approved. Draft Purchase Order {$purchaseOrder->number} created."
            : 'Purchase Request approved.';

        return redirect()
            ->route('tenant.purchase-requests.show', $purchaseRequest->id)
            ->with('success', $message);
    }

    // ── Reject ────────────────────────────────────────────────────
    public function reject(Request $request, int|string $id): RedirectResponse
    {
        $purchaseRequest = $this->findPurchaseRequest($id);
        $this->authorize('approve', $purchaseRequest);

        $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        PurchaseRequestService::reject($purchaseRequest, $request->rejection_reason);

        return redirect()
            ->route('tenant.purchase-requests.show', $purchaseRequest->id)
            ->with('success', 'Purchase Request rejected.');
    }
}
