<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\InstagramAutomation;
use App\Models\InstagramChatbotFlow;
use App\Models\InstagramLog;
use App\Models\InstagramSetting;
use App\Services\InstagramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InstagramController extends Controller
{
    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    // ── Dashboard ─────────────────────────────────────────────────
    public function index(): View
    {
        $settings = InstagramSetting::forTenant($this->tenantId());

        $stats = [
            'automations'  => InstagramAutomation::where('tenant_id', $this->tenantId())->count(),
            'active_auto'  => InstagramAutomation::where('tenant_id', $this->tenantId())->where('is_active', true)->count(),
            'chatbot_flows'=> InstagramChatbotFlow::where('tenant_id', $this->tenantId())->count(),
            'total_logs'   => InstagramLog::where('tenant_id', $this->tenantId())->count(),
            'today_logs'   => InstagramLog::where('tenant_id', $this->tenantId())->whereDate('created_at', today())->count(),
        ];

        $recentLogs = InstagramLog::where('tenant_id', $this->tenantId())
            ->latest()->limit(10)->get();

        return view('tenant.instagram.index', compact('settings', 'stats', 'recentLogs'));
    }

    // ── Settings — show ───────────────────────────────────────────
    public function settings(): View
    {
        $settings = InstagramSetting::forTenant($this->tenantId());
        return view('tenant.instagram.settings', compact('settings'));
    }

    // ── Settings — save ───────────────────────────────────────────
    public function saveSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'app_id'                 => ['nullable', 'string', 'max:255'],
            'app_secret'             => ['nullable', 'string', 'max:255'],
            'access_token'           => ['nullable', 'string'],
            'instagram_account_id'   => ['nullable', 'string', 'max:100'],
            'page_id'                => ['nullable', 'string', 'max:100'],
            'n8n_webhook_url'        => ['nullable', 'url', 'max:500'],
        ]);

        $settings = InstagramSetting::forTenant($this->tenantId());
        $settings->tenant_id = $this->tenantId();

        if ($request->filled('app_id'))               $settings->app_id = $request->app_id;
        if ($request->filled('app_secret'))           $settings->app_secret = $request->app_secret;
        if ($request->filled('access_token'))         $settings->access_token = $request->access_token;
        if ($request->filled('instagram_account_id')) $settings->instagram_account_id = $request->instagram_account_id;
        if ($request->filled('page_id'))              $settings->page_id = $request->page_id;
        if (!$settings->webhook_verify_token)         $settings->webhook_verify_token = Str::random(32);

        $settings->n8n_webhook_url = $request->n8n_webhook_url;
        $settings->save();

        return back()->with('success', 'Instagram settings saved successfully.');
    }

    // ── Settings — test connection ────────────────────────────────
    public function testConnection(): JsonResponse
    {
        try {
            $service = InstagramService::forTenant($this->tenantId());
            $info    = $service->getAccountInfo();

            if (!$info) {
                return response()->json(['success' => false, 'message' => 'Could not connect. Check credentials.']);
            }

            InstagramSetting::where('tenant_id', $this->tenantId())
                ->update(['is_connected' => true]);

            return response()->json(['success' => true, 'account' => $info]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ── Automations — list ────────────────────────────────────────
    public function automations(): View
    {
        $automations = InstagramAutomation::where('tenant_id', $this->tenantId())
            ->orderByDesc('is_active')->orderBy('name')->paginate(15);

        return view('tenant.instagram.automations.index', compact('automations'));
    }

    // ── Automations — create form ─────────────────────────────────
    public function createAutomation(): View
    {
        return view('tenant.instagram.automations.create');
    }

    // ── Automations — store ───────────────────────────────────────
    public function storeAutomation(Request $request): RedirectResponse
    {
        $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'trigger_type'    => ['required', 'in:any_post_comment,specific_post_comment,dm_keyword'],
            'post_id'         => ['nullable', 'string', 'max:100'],
            'trigger_keywords'=> ['nullable', 'string'],
            'keyword_match'   => ['required', 'in:any,exact,contains'],
            'action_type'     => ['required', 'in:send_dm,reply_comment,trigger_n8n'],
            'dm_message'      => ['nullable', 'string', 'max:1000'],
            'comment_reply'   => ['nullable', 'string', 'max:1000'],
            'n8n_webhook_url' => ['nullable', 'url', 'max:500'],
        ]);

        $keywords = $request->filled('trigger_keywords')
            ? array_map('trim', explode(',', $request->trigger_keywords))
            : [];

        InstagramAutomation::create([
            'tenant_id'        => $this->tenantId(),
            'name'             => $request->name,
            'trigger_type'     => $request->trigger_type,
            'post_id'          => $request->post_id,
            'trigger_keywords' => $keywords,
            'keyword_match'    => $request->keyword_match,
            'action_type'      => $request->action_type,
            'dm_message'       => $request->dm_message,
            'comment_reply'    => $request->comment_reply,
            'n8n_webhook_url'  => $request->n8n_webhook_url,
            'is_active'        => true,
        ]);

        return redirect()->route('tenant.instagram.automations')
            ->with('success', 'Automation created successfully.');
    }

    // ── Automations — edit ────────────────────────────────────────
    public function editAutomation(int $id): View
    {
        $automation = InstagramAutomation::where('id', $id)
            ->where('tenant_id', $this->tenantId())->firstOrFail();

        return view('tenant.instagram.automations.edit', compact('automation'));
    }

    // ── Automations — update ──────────────────────────────────────
    public function updateAutomation(Request $request, int $id): RedirectResponse
    {
        $automation = InstagramAutomation::where('id', $id)
            ->where('tenant_id', $this->tenantId())->firstOrFail();

        $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'trigger_type'    => ['required', 'in:any_post_comment,specific_post_comment,dm_keyword'],
            'post_id'         => ['nullable', 'string'],
            'trigger_keywords'=> ['nullable', 'string'],
            'keyword_match'   => ['required', 'in:any,exact,contains'],
            'action_type'     => ['required', 'in:send_dm,reply_comment,trigger_n8n'],
            'dm_message'      => ['nullable', 'string'],
            'comment_reply'   => ['nullable', 'string'],
            'n8n_webhook_url' => ['nullable', 'url'],
        ]);

        $keywords = $request->filled('trigger_keywords')
            ? array_map('trim', explode(',', $request->trigger_keywords))
            : [];

        $automation->update([
            'name'             => $request->name,
            'trigger_type'     => $request->trigger_type,
            'post_id'          => $request->post_id,
            'trigger_keywords' => $keywords,
            'keyword_match'    => $request->keyword_match,
            'action_type'      => $request->action_type,
            'dm_message'       => $request->dm_message,
            'comment_reply'    => $request->comment_reply,
            'n8n_webhook_url'  => $request->n8n_webhook_url,
        ]);

        return redirect()->route('tenant.instagram.automations')
            ->with('success', 'Automation updated.');
    }

    // ── Automations — toggle ──────────────────────────────────────
    public function toggleAutomation(int $id): JsonResponse
    {
        $automation = InstagramAutomation::where('id', $id)
            ->where('tenant_id', $this->tenantId())->firstOrFail();

        $automation->update(['is_active' => !$automation->is_active]);

        return response()->json(['is_active' => $automation->is_active]);
    }

    // ── Automations — delete ──────────────────────────────────────
    public function destroyAutomation(int $id): RedirectResponse
    {
        InstagramAutomation::where('id', $id)
            ->where('tenant_id', $this->tenantId())->delete();

        return back()->with('success', 'Automation deleted.');
    }

    // ── Chatbot Flows — list ──────────────────────────────────────
    public function chatbot(): View
    {
        $flows = InstagramChatbotFlow::where('tenant_id', $this->tenantId())
            ->orderBy('sort_order')->orderBy('name')->paginate(15);

        return view('tenant.instagram.chatbot.index', compact('flows'));
    }

    // ── Chatbot Flows — store ─────────────────────────────────────
    public function storeChatbotFlow(Request $request): RedirectResponse
    {
        $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'trigger_keywords' => ['required', 'string'],
            'keyword_match'    => ['required', 'in:any,exact,contains'],
            'response_message' => ['required', 'string', 'max:2000'],
            'is_default'       => ['nullable', 'boolean'],
        ]);

        $keywords = array_map('trim', explode(',', $request->trigger_keywords));

        InstagramChatbotFlow::create([
            'tenant_id'        => $this->tenantId(),
            'name'             => $request->name,
            'trigger_keywords' => $keywords,
            'keyword_match'    => $request->keyword_match,
            'response_message' => $request->response_message,
            'is_default'       => (bool) $request->is_default,
            'is_active'        => true,
        ]);

        return back()->with('success', 'Chatbot flow created.');
    }

    // ── Chatbot Flows — update ────────────────────────────────────
    public function updateChatbotFlow(Request $request, int $id): RedirectResponse
    {
        $flow = InstagramChatbotFlow::where('id', $id)
            ->where('tenant_id', $this->tenantId())->firstOrFail();

        $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'trigger_keywords' => ['required', 'string'],
            'keyword_match'    => ['required', 'in:any,exact,contains'],
            'response_message' => ['required', 'string', 'max:2000'],
            'is_default'       => ['nullable', 'boolean'],
        ]);

        $keywords = array_map('trim', explode(',', $request->trigger_keywords));

        $flow->update([
            'name'             => $request->name,
            'trigger_keywords' => $keywords,
            'keyword_match'    => $request->keyword_match,
            'response_message' => $request->response_message,
            'is_default'       => (bool) $request->is_default,
        ]);

        return back()->with('success', 'Chatbot flow updated.');
    }

    // ── Chatbot Flows — toggle ────────────────────────────────────
    public function toggleChatbotFlow(int $id): JsonResponse
    {
        $flow = InstagramChatbotFlow::where('id', $id)
            ->where('tenant_id', $this->tenantId())->firstOrFail();

        $flow->update(['is_active' => !$flow->is_active]);

        return response()->json(['is_active' => $flow->is_active]);
    }

    // ── Chatbot Flows — delete ────────────────────────────────────
    public function destroyChatbotFlow(int $id): RedirectResponse
    {
        InstagramChatbotFlow::where('id', $id)
            ->where('tenant_id', $this->tenantId())->delete();

        return back()->with('success', 'Chatbot flow deleted.');
    }

    // ── Guide / How it works ──────────────────────────────────────
    public function guide(): View
    {
        $settings  = InstagramSetting::forTenant($this->tenantId());
        $webhookUrl = url('/webhook/instagram');
        return view('tenant.instagram.guide', compact('settings', 'webhookUrl'));
    }

    // ── Logs ──────────────────────────────────────────────────────
    public function logs(Request $request): View
    {
        $query = InstagramLog::where('tenant_id', $this->tenantId())->latest();

        if ($request->filled('event_type')) $query->where('event_type', $request->event_type);
        if ($request->filled('status'))     $query->where('status', $request->status);
        if ($request->filled('date'))       $query->whereDate('created_at', $request->date);

        $logs = $query->paginate(20)->withQueryString();

        return view('tenant.instagram.logs', compact('logs'));
    }
}
