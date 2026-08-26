<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Helpers\ViewScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\WorkOrderRequest;
use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\NotificationService;
use App\Services\PurchaseRequestService;
use App\Services\WorkOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WorkOrderController extends Controller
{
    // ── Find work order — tenant scope ──────────────────────────────
    private function findWorkOrder(int|string $id): WorkOrder
    {
        return WorkOrder::where('id', $id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->firstOrFail();
    }

    private function staffList()
    {
        return User::where('tenant_id', auth()->user()->tenant_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    // ── Shared filtered query (index page reuses this) ──────────────
    public static function filteredQuery(int $tenantId, array $filters, User $user): \Illuminate\Database\Eloquent\Builder
    {
        $query = WorkOrder::query()->where('tenant_id', $tenantId);
        $query = ViewScope::apply($query, 'work_orders', $user, 'created_by');

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
            ->with(['product', 'createdBy', 'assignedTo'])
            ->latest();

        $workOrders = $query->paginate(15)->withQueryString();

        $summary = ViewScope::apply(WorkOrder::where('tenant_id', auth()->user()->tenant_id), 'work_orders', auth()->user(), 'created_by')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $counts = [
            'all'         => $summary->sum(),
            'pending'     => $summary->get('pending', 0),
            'in_progress' => $summary->get('in_progress', 0),
            'completed'   => $summary->get('completed', 0),
            'cancelled'   => $summary->get('cancelled', 0),
        ];

        $statuses = WorkOrder::statuses();

        return view('tenant.work-orders.index', compact('workOrders', 'counts', 'statuses'));
    }

    // ── Create ────────────────────────────────────────────────────
    public function create(): View
    {
        $this->authorize('create', WorkOrder::class);

        $products = Product::where('tenant_id', auth()->user()->tenant_id)
            ->active()
            ->finishedGood()
            ->with('billOfMaterials.material')
            ->orderBy('name')
            ->get(['id', 'name', 'product_code', 'current_stock']);

        $number = WorkOrder::generateNumber();
        $staff  = $this->staffList();

        return view('tenant.work-orders.create', compact('products', 'number', 'staff'));
    }

    // ── Store ─────────────────────────────────────────────────────
    public function store(WorkOrderRequest $request): RedirectResponse
    {
        $this->authorize('create', WorkOrder::class);

        $workOrder = WorkOrderService::store($request->validated(), auth()->user()->tenant_id, auth()->id());

        return redirect()
            ->route('tenant.work-orders.show', $workOrder->id)
            ->with('success', "Work Order {$workOrder->number} created successfully.");
    }

    // ── Show ──────────────────────────────────────────────────────
    public function show(int|string $id): View
    {
        $workOrder = $this->findWorkOrder($id);
        $this->authorize('view', $workOrder);
        $workOrder->load(['product.billOfMaterials.material', 'createdBy', 'assignedTo']);

        $shortfall = in_array($workOrder->status, ['pending', 'in_progress'], true)
            ? WorkOrderService::previewShortfall($workOrder->product, (float) $workOrder->quantity)
            : [];

        return view('tenant.work-orders.show', compact('workOrder', 'shortfall'));
    }

    // ── Edit ──────────────────────────────────────────────────────
    public function edit(int|string $id): View
    {
        $workOrder = $this->findWorkOrder($id);
        $this->authorize('modify', $workOrder);

        $products = Product::where('tenant_id', auth()->user()->tenant_id)
            ->active()
            ->finishedGood()
            ->orderBy('name')
            ->get(['id', 'name', 'product_code', 'current_stock']);
        $staff = $this->staffList();

        return view('tenant.work-orders.edit', compact('workOrder', 'products', 'staff'));
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(WorkOrderRequest $request, int|string $id): RedirectResponse
    {
        $workOrder = $this->findWorkOrder($id);
        $this->authorize('modify', $workOrder);

        WorkOrderService::update($workOrder, $request->validated());

        return redirect()
            ->route('tenant.work-orders.show', $workOrder->id)
            ->with('success', 'Work Order updated successfully.');
    }

    // ── Destroy ───────────────────────────────────────────────────
    public function destroy(int|string $id): RedirectResponse
    {
        $workOrder = $this->findWorkOrder($id);
        $this->authorize('delete', $workOrder);
        $number = $workOrder->number;
        $workOrder->delete();

        return redirect()
            ->route('tenant.work-orders.index')
            ->with('success', "Work Order {$number} deleted.");
    }

    // ── Start ─────────────────────────────────────────────────────
    public function start(int|string $id): RedirectResponse
    {
        $workOrder = $this->findWorkOrder($id);
        $this->authorize('manage', $workOrder);

        if (!$workOrder->isPending()) {
            return back()->with('error', 'Only a pending work order can be started.');
        }

        WorkOrderService::start($workOrder);

        return redirect()
            ->route('tenant.work-orders.show', $workOrder->id)
            ->with('success', 'Work Order started.');
    }

    // ── Complete ──────────────────────────────────────────────────
    public function complete(Request $request, int|string $id): RedirectResponse
    {
        $workOrder = $this->findWorkOrder($id);
        $this->authorize('manage', $workOrder);

        if (!$workOrder->isInProgress()) {
            return back()->with('error', 'Only an in-progress work order can be completed.');
        }

        $request->validate(['expiry_date' => ['nullable', 'date']]);

        $shortfall = WorkOrderService::previewShortfall($workOrder->product, (float) $workOrder->quantity);

        if (!empty($shortfall) && $workOrder->tenant->wantsAutoCreatePurchaseRequestOnShortfall()) {
            $purchaseRequest = $this->createShortfallPurchaseRequest($workOrder, $shortfall);

            return back()->with('error', "Cannot complete — insufficient raw material stock. Purchase Request {$purchaseRequest->number} was auto-created for the shortage.");
        }

        try {
            WorkOrderService::complete($workOrder, $request->input('expiry_date'));
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        $this->notifyCompletion($workOrder);

        return redirect()
            ->route('tenant.work-orders.show', $workOrder->id)
            ->with('success', "Work Order {$workOrder->number} completed — stock updated.");
    }

    // ── Auto-create a Purchase Request for a Work Order's raw material
    // shortfall (only reached when the tenant has opted into the
    // auto-PR-on-shortfall setting) and notify whoever can approve it. ──
    private function createShortfallPurchaseRequest(WorkOrder $workOrder, array $shortfall): PurchaseRequest
    {
        $items = collect($shortfall)->map(fn ($row) => [
            'product_id'  => $row['material_id'],
            'name'        => $row['name'],
            'description' => null,
            'quantity'    => $row['shortfall'],
            'reason'      => "Shortfall for Work Order {$workOrder->number}",
        ])->toArray();

        $purchaseRequest = PurchaseRequestService::store([
            'department_id' => null,
            'date'           => now()->toDateString(),
            'items'          => $items,
            'reason'         => "Auto-created — Work Order {$workOrder->number} ({$workOrder->product?->name}) could not be completed due to insufficient raw material stock.",
        ], $workOrder->tenant_id, auth()->id());

        $this->notifyPurchaseRequestApprovers($purchaseRequest);

        return $purchaseRequest;
    }

    // ── Notify everyone who can approve requests — same recipients/type
    // as a manually submitted Purchase Request. ─────────────────────
    private function notifyPurchaseRequestApprovers(PurchaseRequest $purchaseRequest): void
    {
        $approvers = User::withoutGlobalScopes()
            ->where('tenant_id', $purchaseRequest->tenant_id)
            ->where('is_active', true)
            ->where('id', '!=', auth()->id())
            ->get()
            ->filter(fn (User $user) => $user->user_type === 'tenant_admin' || $user->can('purchase_requests.approve'));

        app(NotificationService::class)->sendToMany(
            'purchase_request.submitted',
            $approvers->all(),
            ['number' => $purchaseRequest->number, 'requester' => auth()->user()->name],
            auth()->user(),
            route('tenant.purchase-requests.show', $purchaseRequest->id),
            $purchaseRequest
        );
    }

    // ── Toggle: auto-create a Purchase Request when a Work Order can't
    // complete due to raw material shortage. Tenant-owner-controlled. ──
    public function updateAutoPrSetting(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->user_type === 'tenant_admin', 403);

        $tenant = auth()->user()->tenant;
        $settings = $tenant->settings ?? [];
        $settings['preferences'] ??= [];
        $settings['preferences']['auto_create_pr_on_shortfall'] = $request->boolean('auto_create_pr');
        $tenant->update(['settings' => $settings]);

        return back()->with('success', 'Setting updated.');
    }

    // ── Notify tenant_admins / work_orders.view_all holders that a
    // production run finished and stock was updated ────────────────
    private function notifyCompletion(WorkOrder $workOrder): void
    {
        $recipients = User::withoutGlobalScopes()
            ->where('tenant_id', $workOrder->tenant_id)
            ->where('is_active', true)
            ->where('id', '!=', auth()->id())
            ->get()
            ->filter(fn (User $user) => $user->user_type === 'tenant_admin' || $user->can('work_orders.view_all'));

        app(NotificationService::class)->sendToMany(
            'work_order.completed',
            $recipients->all(),
            [
                'number'   => $workOrder->number,
                'quantity' => (float) $workOrder->quantity,
                'product'  => $workOrder->product?->name,
            ],
            auth()->user(),
            route('tenant.work-orders.show', $workOrder->id),
            $workOrder
        );
    }

    // ── Update labor/machine costs — available regardless of status ──
    public function updateCosts(Request $request, int|string $id): RedirectResponse
    {
        $workOrder = $this->findWorkOrder($id);
        $this->authorize('editCosts', $workOrder);

        $data = $request->validate([
            'labor_cost'   => ['nullable', 'numeric', 'min:0'],
            'machine_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $workOrder->update([
            'labor_cost'   => $data['labor_cost'] ?? 0,
            'machine_cost' => $data['machine_cost'] ?? 0,
        ]);

        return redirect()
            ->route('tenant.work-orders.show', $workOrder->id)
            ->with('success', 'Production costs updated.');
    }

    // ── Cancel ────────────────────────────────────────────────────
    public function cancel(int|string $id): RedirectResponse
    {
        $workOrder = $this->findWorkOrder($id);
        $this->authorize('manage', $workOrder);

        WorkOrderService::cancel($workOrder);

        return redirect()
            ->route('tenant.work-orders.show', $workOrder->id)
            ->with('success', 'Work Order cancelled.');
    }
}
