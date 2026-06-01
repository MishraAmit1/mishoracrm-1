<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TenantIntegration;
use App\Services\Integrations\IndiaMartLeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LeadIntegrationController extends Controller
{
    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    private function tenant(): \App\Models\Tenant
    {
        return app('tenant');
    }

    // ── Index: show all integrations for this tenant ──────────────

    public function index(): View
    {
        $tenant  = $this->tenant();
        $allowed = TenantIntegration::getAllowedPlatforms($tenant);

        // Load existing configs
        $integrations = TenantIntegration::where('tenant_id', $this->tenantId())
            ->get()
            ->keyBy('platform');

        $platforms = collect(TenantIntegration::PLATFORMS)
            ->map(function ($info, $platform) use ($allowed, $integrations) {
                return [
                    'key'           => $platform,
                    'info'          => $info,
                    'allowed'       => in_array($platform, $allowed),
                    'integration'   => $integrations->get($platform),
                ];
            });

        return view('tenant.lead-integrations.index', compact('platforms'));
    }

    // ── Setup / Config page for a single platform ─────────────────

    public function setup(string $platform): View|RedirectResponse
    {
        $tenant = $this->tenant();

        if (!TenantIntegration::isPlatformAllowed($tenant, $platform)) {
            return redirect()
                ->route('tenant.lead-integrations.index')
                ->with('error', 'This integration is not enabled for your account. Please contact support.');
        }

        if (!array_key_exists($platform, TenantIntegration::PLATFORMS)) {
            abort(404);
        }

        $integration = TenantIntegration::forTenant($this->tenantId(), $platform);

        return view('tenant.lead-integrations.setup', compact('integration', 'platform'));
    }

    // ── Save credentials for a platform ──────────────────────────

    public function save(Request $request, string $platform): RedirectResponse
    {
        $tenant = $this->tenant();

        if (!TenantIntegration::isPlatformAllowed($tenant, $platform)) {
            return back()->with('error', 'Integration not allowed.');
        }

        $integration = TenantIntegration::forTenant($this->tenantId(), $platform);

        $credentials = match ($platform) {
            'meta_lead_ads' => $request->only(['page_access_token', 'app_secret', 'verify_token']),
            'indiamart'     => $request->only(['glusr_usr_given_code', 'mobile']),
            'justdial'      => $request->only(['secret_key']),
            'tradeindia'    => $request->only(['api_key']),
            'sulekha'       => $request->only(['api_key']),
            default         => [],
        };

        $settings = [];
        if ($platform === 'meta_lead_ads') {
            $formIds = array_filter(array_map('trim', explode(',', $request->input('form_ids', ''))));
            $settings['form_ids'] = $formIds;
        }

        $credentials = array_filter($credentials, fn($v) => !is_null($v) && $v !== '');

        $integration->update([
            'credentials' => $credentials,
            'settings'    => $settings ?: null,
            'is_active'   => (bool) $request->is_active,
        ]);

        return redirect()
            ->route('tenant.lead-integrations.setup', $platform)
            ->with('success', 'Integration settings saved.');
    }

    // ── Regenerate webhook token ───────────────────────────────────

    public function regenerateToken(string $platform): RedirectResponse
    {
        $integration = TenantIntegration::where('tenant_id', $this->tenantId())
            ->where('platform', $platform)
            ->firstOrFail();

        $integration->update(['webhook_token' => Str::random(40)]);

        return back()->with('success', 'Webhook URL regenerated. Update it in your platform settings.');
    }

    // ── Manual sync (IndiaMART) ────────────────────────────────────

    public function syncNow(string $platform): JsonResponse|RedirectResponse
    {
        $integration = TenantIntegration::where('tenant_id', $this->tenantId())
            ->where('platform', $platform)
            ->where('is_active', true)
            ->firstOrFail();

        if ($platform === 'indiamart') {
            $service  = IndiaMartLeadService::forIntegration($integration);
            $imported = $service->sync();

            return back()->with('success', "Sync complete. {$imported} new lead(s) imported.");
        }

        return back()->with('error', 'Manual sync is only available for IndiaMART.');
    }

    // ── Test credentials (AJAX) ────────────────────────────────────

    public function testConnection(string $platform): JsonResponse
    {
        $integration = TenantIntegration::where('tenant_id', $this->tenantId())
            ->where('platform', $platform)
            ->firstOrFail();

        if ($platform === 'indiamart') {
            $service = IndiaMartLeadService::forIntegration($integration);
            $ok      = $service->testCredentials();

            return response()->json(['ok' => $ok, 'message' => $ok ? 'Connection successful!' : 'Connection failed. Check your API key.']);
        }

        return response()->json(['ok' => true, 'message' => 'Webhook integrations are tested when a real lead arrives.']);
    }
}
