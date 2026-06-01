<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantIntegration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Super Admin controls which integrations are allowed per tenant.
 * Stored in Tenant.settings['integrations'] JSON.
 */
class LeadIntegrationController extends Controller
{
    // ── Index: all tenants with their integration access ──────────

    public function index(): View
    {
        $tenants = Tenant::where('status', 'active')
            ->with('subscription.plan')
            ->orderBy('name')
            ->get()
            ->map(function (Tenant $tenant) {
                $allowed     = $tenant->settings['integrations'] ?? [];
                $stats       = TenantIntegration::where('tenant_id', $tenant->id)->get();
                return [
                    'tenant'       => $tenant,
                    'allowed'      => $allowed,
                    'active_count' => $stats->where('is_active', true)->count(),
                    'total_leads'  => $stats->sum('leads_imported'),
                ];
            });

        $platforms = TenantIntegration::PLATFORMS;

        return view('superadmin.lead-integrations.index', compact('tenants', 'platforms'));
    }

    // ── Edit: grant/revoke integrations for a specific tenant ─────

    public function edit(Tenant $tenant): View
    {
        $platforms   = TenantIntegration::PLATFORMS;
        $allowed     = $tenant->settings['integrations'] ?? [];
        $stats       = TenantIntegration::where('tenant_id', $tenant->id)->get()->keyBy('platform');

        return view('superadmin.lead-integrations.edit', compact('tenant', 'platforms', 'allowed', 'stats'));
    }

    // ── Update: save which integrations are allowed ───────────────

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $platformKeys = array_keys(TenantIntegration::PLATFORMS);

        $integrations = [];
        foreach ($platformKeys as $key) {
            $integrations[$key] = $request->boolean("integrations.{$key}");
        }

        $settings                 = $tenant->settings ?? [];
        $settings['integrations'] = $integrations;
        $tenant->update(['settings' => $settings]);

        return redirect()
            ->route('superadmin.lead-integrations.index')
            ->with('success', "Integration access updated for {$tenant->name}.");
    }

    // ── Quick toggle (AJAX) ───────────────────────────────────────

    public function toggle(Request $request, Tenant $tenant): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'platform' => ['required', 'in:' . implode(',', array_keys(TenantIntegration::PLATFORMS))],
            'enabled'  => ['required', 'boolean'],
        ]);

        $settings = $tenant->settings ?? [];
        $settings['integrations'][$request->platform] = $request->boolean('enabled');
        $tenant->update(['settings' => $settings]);

        return response()->json(['ok' => true]);
    }
}
