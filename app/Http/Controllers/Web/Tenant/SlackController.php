<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TenantSlackConfig;
use App\Services\SlackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SlackController extends Controller
{
    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    public function index(): View
    {
        $config = TenantSlackConfig::where('tenant_id', $this->tenantId())->first();

        return view('tenant.slack.index', compact('config'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'webhook_url' => ['required', 'url', 'max:500', 'starts_with:https://hooks.slack.com/'],
        ]);

        TenantSlackConfig::updateOrCreate(
            ['tenant_id' => $this->tenantId()],
            ['webhook_url' => $request->webhook_url, 'is_active' => true]
        );

        return back()->with('success', 'Slack webhook saved.');
    }

    public function toggle(): RedirectResponse
    {
        $config = TenantSlackConfig::where('tenant_id', $this->tenantId())->firstOrFail();

        $config->update(['is_active' => ! $config->is_active]);

        return back()->with('success', $config->is_active ? 'Slack notifications enabled.' : 'Slack notifications disabled.');
    }

    public function destroy(): RedirectResponse
    {
        TenantSlackConfig::where('tenant_id', $this->tenantId())->delete();

        return back()->with('success', 'Slack webhook removed.');
    }

    public function test(): JsonResponse
    {
        $config = TenantSlackConfig::where('tenant_id', $this->tenantId())->firstOrFail();

        $result = SlackService::test($config->webhook_url);

        $config->update(['last_tested_at' => now()]);

        return response()->json($result);
    }
}
