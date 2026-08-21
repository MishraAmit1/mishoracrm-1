<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    private function findService(int|string $id): Service
    {
        return Service::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();
    }

    // ── Index ─────────────────────────────────────────────────────
    public function index(Request $request): View
    {
        $query = Service::with('packageComponents')->where('tenant_id', $this->tenantId())->latest();

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $services = $query->paginate(20)->withQueryString();

        return view('tenant.services.index', compact('services'));
    }

    // ── Create ────────────────────────────────────────────────────
    public function create(): View
    {
        $availableComponents = Service::where('tenant_id', $this->tenantId())->standalone()->active()->orderBy('name')->get(['id', 'name', 'rate']);

        return view('tenant.services.create', compact('availableComponents'));
    }

    // ── Store ─────────────────────────────────────────────────────
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $data['tenant_id']     = $this->tenantId();
        $data['is_active']     = $request->boolean('is_active', true);
        $data['billing_cycle'] = $data['billing_cycle'] ?? 'one_time';
        $data['is_package']    = $request->boolean('is_package');

        $service = Service::create($data);

        if ($service->is_package) {
            $this->syncComponents($service, $request);
        }

        return redirect()->route('tenant.services.index')
            ->with('success', ($service->is_package ? 'Package' : 'Service') . ' added successfully.');
    }

    // ── Edit ──────────────────────────────────────────────────────
    public function edit(int|string $id): View
    {
        $service = $this->findService($id);
        $availableComponents = Service::where('tenant_id', $this->tenantId())
            ->standalone()->active()->where('id', '!=', $service->id)->orderBy('name')->get(['id', 'name', 'rate']);
        $selectedComponentIds = $service->packageComponents->pluck('id')->toArray();

        return view('tenant.services.edit', compact('service', 'availableComponents', 'selectedComponentIds'));
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(Request $request, int|string $id): RedirectResponse
    {
        $service = $this->findService($id);

        $data = $this->validated($request);

        $data['is_active']     = $request->boolean('is_active', true);
        $data['billing_cycle'] = $data['billing_cycle'] ?? 'one_time';
        $data['is_package']    = $request->boolean('is_package');

        $service->update($data);

        if ($service->is_package) {
            $this->syncComponents($service, $request);
        } else {
            $service->packageComponents()->detach();
        }

        return redirect()->route('tenant.services.index')
            ->with('success', ($service->is_package ? 'Package' : 'Service') . ' updated successfully.');
    }

    // ── Destroy ───────────────────────────────────────────────────
    public function destroy(int|string $id): RedirectResponse
    {
        $this->findService($id)->delete();

        return redirect()->route('tenant.services.index')
            ->with('success', 'Service deleted.');
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'service_code'    => ['nullable', 'string', 'max:50'],
            'description'     => ['nullable', 'string'],
            'rate'            => ['required', 'numeric', 'min:0'],
            'tax_percent'     => ['required', 'numeric', 'min:0', 'max:100'],
            'hsn'             => ['nullable', 'string', 'max:50'],
            'unit'            => ['nullable', 'string', 'max:50'],
            'is_active'       => ['nullable', 'boolean'],
            'billing_cycle'   => ['nullable', 'in:one_time,monthly,quarterly,yearly'],
            'duration_value'  => ['nullable', 'integer', 'min:1'],
            'duration_unit'   => ['nullable', 'in:days,months'],
            'total_quantity'  => ['nullable', 'integer', 'min:1'],
            'is_package'          => ['nullable', 'boolean'],
            'component_service_ids'   => ['nullable', 'array'],
            'component_service_ids.*' => ['integer', 'exists:services,id'],
        ]);
    }

    // Only sync components that belong to this tenant and aren't packages
    // themselves (keeps bundles one level deep — no packages-of-packages).
    private function syncComponents(Service $package, Request $request): void
    {
        $ids = collect($request->input('component_service_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id !== $package->id);

        $validIds = Service::where('tenant_id', $this->tenantId())
            ->standalone()
            ->whereIn('id', $ids)
            ->pluck('id');

        $package->packageComponents()->sync(
            $validIds->mapWithKeys(fn ($id) => [$id => ['tenant_id' => $this->tenantId()]])
        );
    }
}
