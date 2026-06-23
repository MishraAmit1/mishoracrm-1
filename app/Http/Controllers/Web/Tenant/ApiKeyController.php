<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApiKeyController extends Controller
{
    private function tenantId(): int
    {
        return app('tenant_id');
    }

    // ── Index ─────────────────────────────────────────────────────
    public function index(): View
    {
        $apiKeys = ApiKey::where('tenant_id', $this->tenantId())
            ->with('creator:id,name')
            ->latest()
            ->get();

        return view('tenant.api-keys.index', compact('apiKeys'));
    }

    // ── Store ─────────────────────────────────────────────────────
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        ApiKey::generate($this->tenantId(), auth()->id(), $request->name);

        return back()->with('success', 'API key generated successfully.');
    }

    // ── Toggle active/inactive ────────────────────────────────────
    public function toggle(int $id): RedirectResponse
    {
        $key = ApiKey::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();

        $key->update(['is_active' => !$key->is_active]);

        $status = $key->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "API key {$status}.");
    }

    // ── Regenerate key value ──────────────────────────────────────
    public function regenerate(int $id): RedirectResponse
    {
        $key = ApiKey::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();

        $key->update(['key' => 'crm_' . bin2hex(random_bytes(24))]);

        return back()->with('success', 'API key regenerated. Update your integrations with the new key.');
    }

    // ── Destroy ───────────────────────────────────────────────────
    public function destroy(int $id): RedirectResponse
    {
        $key = ApiKey::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();

        $key->delete();

        return back()->with('success', 'API key revoked and deleted.');
    }
}
