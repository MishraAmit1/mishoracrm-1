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
        $query = Service::where('tenant_id', $this->tenantId())->latest();

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $services = $query->paginate(20)->withQueryString();

        return view('tenant.services.index', compact('services'));
    }

    // ── Create ────────────────────────────────────────────────────
    public function create(): View
    {
        return view('tenant.services.create');
    }

    // ── Store ─────────────────────────────────────────────────────
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
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
        ]);

        $data['tenant_id']     = $this->tenantId();
        $data['is_active']     = $request->boolean('is_active', true);
        $data['billing_cycle'] = $data['billing_cycle'] ?? 'one_time';

        Service::create($data);

        return redirect()->route('tenant.services.index')
            ->with('success', 'Service added successfully.');
    }

    // ── Edit ──────────────────────────────────────────────────────
    public function edit(int|string $id): View
    {
        $service = $this->findService($id);

        return view('tenant.services.edit', compact('service'));
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(Request $request, int|string $id): RedirectResponse
    {
        $service = $this->findService($id);

        $data = $request->validate([
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
        ]);

        $data['is_active']     = $request->boolean('is_active', true);
        $data['billing_cycle'] = $data['billing_cycle'] ?? 'one_time';

        $service->update($data);

        return redirect()->route('tenant.services.index')
            ->with('success', 'Service updated successfully.');
    }

    // ── Destroy ───────────────────────────────────────────────────
    public function destroy(int|string $id): RedirectResponse
    {
        $this->findService($id)->delete();

        return redirect()->route('tenant.services.index')
            ->with('success', 'Service deleted.');
    }
}
