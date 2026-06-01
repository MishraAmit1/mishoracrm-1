<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\WhatsappChatbotFlow;
use App\Models\WhatsappChatbotSession;
use App\Models\WhatsappSetting;
use App\Services\WhatsappChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WhatsappChatbotController extends Controller
{
    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    // ── Settings — show ───────────────────────────────────────────
    public function settings(): View
    {
        $settings = WhatsappSetting::forTenant($this->tenantId());
        return view('tenant.whatsapp.chatbot-settings', compact('settings'));
    }

    // ── Settings — save ───────────────────────────────────────────
    public function saveSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'phone_number_id' => ['nullable', 'string', 'max:100'],
            'waba_id'         => ['nullable', 'string', 'max:100'],
            'access_token'    => ['nullable', 'string'],
            'n8n_webhook_url' => ['nullable', 'url', 'max:500'],
            'chatbot_enabled' => ['nullable', 'boolean'],
        ]);

        $settings = WhatsappSetting::forTenant($this->tenantId());
        $settings->tenant_id = $this->tenantId();

        if ($request->filled('phone_number_id')) $settings->phone_number_id = $request->phone_number_id;
        if ($request->filled('waba_id'))         $settings->waba_id = $request->waba_id;
        if ($request->filled('access_token'))    $settings->access_token = $request->access_token;
        if (!$settings->webhook_verify_token)    $settings->webhook_verify_token = Str::random(32);

        $settings->n8n_webhook_url  = $request->n8n_webhook_url;
        $settings->chatbot_enabled  = (bool) $request->chatbot_enabled;
        $settings->save();

        return back()->with('success', 'WhatsApp settings saved successfully.');
    }

    // ── Settings — test connection ────────────────────────────────
    public function testConnection(): JsonResponse
    {
        try {
            $service = WhatsappChatbotService::forTenant($this->tenantId());
            $info    = $service->getAccountInfo();

            if (!$info) {
                return response()->json(['success' => false, 'message' => 'Connection failed. Check credentials.']);
            }

            WhatsappSetting::where('tenant_id', $this->tenantId())
                ->update(['is_connected' => true]);

            return response()->json(['success' => true, 'account' => $info]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ── Chatbot Flows — list ──────────────────────────────────────
    public function flows(): View
    {
        $settings = WhatsappSetting::forTenant($this->tenantId());

        $flows = WhatsappChatbotFlow::where('tenant_id', $this->tenantId())
            ->orderBy('sort_order')->orderBy('name')->get();

        $sessions = WhatsappChatbotSession::where('tenant_id', $this->tenantId())
            ->latest('last_message_at')->limit(10)->get();

        return view('tenant.whatsapp.chatbot', compact('settings', 'flows', 'sessions'));
    }

    // ── Chatbot Flows — store ─────────────────────────────────────
    public function storeFlow(Request $request): RedirectResponse
    {
        $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'trigger_keywords' => ['required', 'string'],
            'keyword_match'    => ['required', 'in:any,exact,contains'],
            'response_message' => ['required', 'string', 'max:4096'],
            'n8n_webhook_url'  => ['nullable', 'url', 'max:500'],
            'is_default'       => ['nullable'],
        ]);

        $keywords = array_map('trim', explode(',', $request->trigger_keywords));

        WhatsappChatbotFlow::create([
            'tenant_id'        => $this->tenantId(),
            'name'             => $request->name,
            'trigger_keywords' => $keywords,
            'keyword_match'    => $request->keyword_match,
            'response_message' => $request->response_message,
            'n8n_webhook_url'  => $request->n8n_webhook_url,
            'is_default'       => (bool) $request->is_default,
            'is_active'        => true,
        ]);

        return back()->with('success', 'Chatbot flow created.');
    }

    // ── Chatbot Flows — update ────────────────────────────────────
    public function updateFlow(Request $request, int $id): RedirectResponse
    {
        $flow = WhatsappChatbotFlow::where('id', $id)
            ->where('tenant_id', $this->tenantId())->firstOrFail();

        $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'trigger_keywords' => ['required', 'string'],
            'keyword_match'    => ['required', 'in:any,exact,contains'],
            'response_message' => ['required', 'string', 'max:4096'],
            'n8n_webhook_url'  => ['nullable', 'url'],
            'is_default'       => ['nullable'],
        ]);

        $keywords = array_map('trim', explode(',', $request->trigger_keywords));

        $flow->update([
            'name'             => $request->name,
            'trigger_keywords' => $keywords,
            'keyword_match'    => $request->keyword_match,
            'response_message' => $request->response_message,
            'n8n_webhook_url'  => $request->n8n_webhook_url,
            'is_default'       => (bool) $request->is_default,
        ]);

        return back()->with('success', 'Chatbot flow updated.');
    }

    // ── Chatbot Flows — toggle ────────────────────────────────────
    public function toggleFlow(int $id): JsonResponse
    {
        $flow = WhatsappChatbotFlow::where('id', $id)
            ->where('tenant_id', $this->tenantId())->firstOrFail();

        $flow->update(['is_active' => !$flow->is_active]);

        return response()->json(['is_active' => $flow->is_active]);
    }

    // ── Chatbot Flows — delete ────────────────────────────────────
    public function destroyFlow(int $id): RedirectResponse
    {
        WhatsappChatbotFlow::where('id', $id)
            ->where('tenant_id', $this->tenantId())->delete();

        return back()->with('success', 'Chatbot flow deleted.');
    }
}
