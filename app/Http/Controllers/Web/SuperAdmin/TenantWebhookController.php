<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantWebhook;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantWebhookController extends Controller
{
    public function index(Tenant $tenant): View
    {
        $webhooks = TenantWebhook::where('tenant_id', $tenant->id)
            ->orderBy('event')
            ->get();

        $events = TenantWebhook::events();

        return view('superadmin.tenant-webhooks.index', compact('tenant', 'webhooks', 'events'));
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $request->validate([
            'event'       => ['required', Rule::in(array_keys(TenantWebhook::events()))],
            'webhook_url' => ['required', 'url', 'max:500'],
        ]);

        TenantWebhook::create([
            'tenant_id'   => $tenant->id,
            'event'       => $request->event,
            'webhook_url' => $request->webhook_url,
            'is_active'   => true,
        ]);

        return back()->with('success', 'Webhook added successfully.');
    }

    public function update(Request $request, Tenant $tenant, TenantWebhook $webhook): RedirectResponse
    {
        $request->validate([
            'webhook_url' => ['required', 'url', 'max:500'],
        ]);

        $webhook->update(['webhook_url' => $request->webhook_url]);

        return back()->with('success', 'Webhook URL updated.');
    }

    public function destroy(Tenant $tenant, TenantWebhook $webhook): RedirectResponse
    {
        $webhook->delete();

        return back()->with('success', 'Webhook deleted.');
    }

    public function toggle(Tenant $tenant, TenantWebhook $webhook): RedirectResponse
    {
        $webhook->update(['is_active' => ! $webhook->is_active]);

        $msg = $webhook->is_active ? 'Webhook activated.' : 'Webhook deactivated.';

        return back()->with('success', $msg);
    }

    public function test(Tenant $tenant, TenantWebhook $webhook): JsonResponse
    {
        $result = WebhookService::test($webhook->webhook_url, $webhook->event, $tenant->id);

        return response()->json($result);
    }

    public function regenerateToken(Tenant $tenant): RedirectResponse
    {
        $tenant->regenerateWebhookToken();

        return back()->with('success', 'Webhook token regenerated. Old token is no longer valid — n8n configuration update karo.');
    }
}
