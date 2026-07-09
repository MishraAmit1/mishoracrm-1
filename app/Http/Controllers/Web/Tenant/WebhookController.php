<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TenantWebhook;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WebhookController extends Controller
{
    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    public function index(): View
    {
        $webhooks = TenantWebhook::where('tenant_id', $this->tenantId())
            ->orderBy('event')
            ->get();

        $events = TenantWebhook::events();

        return view('tenant.webhooks.index', compact('webhooks', 'events'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'event'       => ['required', Rule::in(array_keys(TenantWebhook::events()))],
            'webhook_url' => ['required', 'url', 'max:500'],
        ]);

        TenantWebhook::create([
            'tenant_id'   => $this->tenantId(),
            'event'       => $request->event,
            'webhook_url' => $request->webhook_url,
            'is_active'   => true,
        ]);

        return back()->with('success', 'Webhook added successfully.');
    }

    public function update(Request $request, TenantWebhook $webhook): RedirectResponse
    {
        abort_unless($webhook->tenant_id === $this->tenantId(), 404);

        $request->validate([
            'webhook_url' => ['required', 'url', 'max:500'],
        ]);

        $webhook->update(['webhook_url' => $request->webhook_url]);

        return back()->with('success', 'Webhook URL updated.');
    }

    public function destroy(TenantWebhook $webhook): RedirectResponse
    {
        abort_unless($webhook->tenant_id === $this->tenantId(), 404);

        $webhook->delete();

        return back()->with('success', 'Webhook deleted.');
    }

    public function toggle(TenantWebhook $webhook): RedirectResponse
    {
        abort_unless($webhook->tenant_id === $this->tenantId(), 404);

        $webhook->update(['is_active' => ! $webhook->is_active]);

        $msg = $webhook->is_active ? 'Webhook activated.' : 'Webhook deactivated.';

        return back()->with('success', $msg);
    }

    public function test(TenantWebhook $webhook): JsonResponse
    {
        abort_unless($webhook->tenant_id === $this->tenantId(), 404);

        $result = WebhookService::test($webhook->webhook_url, $webhook->event, $this->tenantId());

        return response()->json($result);
    }

    public function regenerateToken(): RedirectResponse
    {
        auth()->user()->tenant->regenerateWebhookToken();

        return back()->with('success', 'Webhook token regenerated. Old token is no longer valid — n8n mein naya token update karo.');
    }
}
