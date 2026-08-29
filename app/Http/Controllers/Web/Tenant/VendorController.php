<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\VendorRequest;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorController extends Controller
{
    // ── Find vendor — tenant scope ──────────────────────────────────
    private function findVendor(int|string $id): Vendor
    {
        return Vendor::where('id', $id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->firstOrFail();
    }

    // ── Index ─────────────────────────────────────────────────────
    public function index(Request $request): View
    {
        $query = Vendor::where('tenant_id', auth()->user()->tenant_id);

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $vendors = $query->latest()->paginate(15)->withQueryString();

        return view('tenant.vendors.index', compact('vendors'));
    }

    // ── Create ────────────────────────────────────────────────────
    public function create(): View
    {
        $this->authorize('create', Vendor::class);

        return view('tenant.vendors.create');
    }

    // ── Store ─────────────────────────────────────────────────────
    public function store(VendorRequest $request): RedirectResponse
    {
        $this->authorize('create', Vendor::class);

        $vendor = Vendor::create(array_merge($request->validated(), [
            'tenant_id' => auth()->user()->tenant_id,
        ]));

        return redirect()
            ->route('tenant.vendors.show', $vendor->id)
            ->with('success', "Vendor {$vendor->name} created successfully.");
    }

    // ── Show ──────────────────────────────────────────────────────
    public function show(int|string $id): View
    {
        $vendor = $this->findVendor($id);
        $this->authorize('view', $vendor);
        $vendor->load([
            'purchaseOrders' => fn ($q) => $q->latest()->limit(20),
            'bills'          => fn ($q) => $q->latest()->limit(20),
        ]);

        $outstanding = $vendor->outstandingAmount();

        return view('tenant.vendors.show', compact('vendor', 'outstanding'));
    }

    // ── Edit ──────────────────────────────────────────────────────
    public function edit(int|string $id): View
    {
        $vendor = $this->findVendor($id);
        $this->authorize('modify', $vendor);

        return view('tenant.vendors.edit', compact('vendor'));
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(VendorRequest $request, int|string $id): RedirectResponse
    {
        $vendor = $this->findVendor($id);
        $this->authorize('modify', $vendor);

        $vendor->update($request->validated());

        return redirect()
            ->route('tenant.vendors.show', $vendor->id)
            ->with('success', 'Vendor updated successfully.');
    }

    // ── Destroy ───────────────────────────────────────────────────
    public function destroy(int|string $id): RedirectResponse
    {
        $vendor = $this->findVendor($id);
        $this->authorize('delete', $vendor);
        $name = $vendor->name;
        $vendor->delete();

        return redirect()
            ->route('tenant.vendors.index')
            ->with('success', "Vendor {$name} deleted.");
    }
}
