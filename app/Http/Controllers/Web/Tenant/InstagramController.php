<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\InstagramAutomation;
use App\Models\InstagramChatbotFlow;
use App\Models\InstagramLog;
use App\Models\InstagramSetting;
use App\Models\PlatformSetting;
use App\Services\InstagramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
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

        $accountInfo = $this->fetchAccountInfo($settings);

        return view('tenant.instagram.index', compact('settings', 'stats', 'recentLogs', 'accountInfo'));
    }

    // ── Settings — show ───────────────────────────────────────────
    public function settings(): View
    {
        $settings = InstagramSetting::forTenant($this->tenantId());
        $accountInfo = $this->fetchAccountInfo($settings);

        return view('tenant.instagram.settings', compact('settings', 'accountInfo'));
    }

    // ── Fetch the connected account's username/profile so tenant admins
    //    can see WHICH Instagram account is linked, not just a numeric ID ──
    private function fetchAccountInfo(InstagramSetting $settings): ?array
    {
        if (!$settings->is_connected || !$settings->instagram_account_id) {
            return null;
        }

        try {
            return InstagramService::forTenant($settings->tenant_id)->getAccountInfo();
        } catch (\Throwable $e) {
            return null;
        }
    }

    // ── Settings — save ───────────────────────────────────────────
    public function saveSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'instagram_account_id' => ['nullable', 'string', 'max:100'],
            'page_id'              => ['nullable', 'string', 'max:100'],
        ]);

        $settings = InstagramSetting::forTenant($this->tenantId());
        $settings->tenant_id = $this->tenantId();

        if ($request->filled('instagram_account_id')) $settings->instagram_account_id = $request->instagram_account_id;
        if ($request->filled('page_id'))              $settings->page_id = $request->page_id;
        if (!$settings->webhook_verify_token)         $settings->webhook_verify_token = Str::random(32);

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

            // Re-subscribe on every test — cheap, idempotent, and repairs
            // connections that were made before webhook subscription existed.
            $service->subscribeWebhook();

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
            'action_type'     => ['required', 'in:send_dm,reply_comment'],
            'dm_message'      => ['nullable', 'string', 'max:1000'],
            'comment_reply'   => ['nullable', 'string', 'max:1000'],
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
            'is_active'        => true,
        ]);

        return redirect()->route('tenant.instagram.automations')
            ->with('success', 'Automation created successfully.');
    }

    // ── Automations — fetch recent posts for the picker ───────────
    public function fetchPosts(): JsonResponse
    {
        try {
            $posts = InstagramService::forTenant($this->tenantId())->getRecentMedia();
            return response()->json(['success' => true, 'posts' => $posts]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'posts' => [], 'message' => $e->getMessage()]);
        }
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
            'action_type'     => ['required', 'in:send_dm,reply_comment'],
            'dm_message'      => ['nullable', 'string'],
            'comment_reply'   => ['nullable', 'string'],
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

    // ── OAuth — Generate QR (authenticated) ──────────────────────
    public function oauthGenerateQr(): JsonResponse
    {
        $appId     = PlatformSetting::get('meta_app_id');
        $appSecret = PlatformSetting::get('meta_app_secret');

        if (!$appId || !$appSecret) {
            return response()->json(['success' => false, 'message' => 'Meta App credentials not configured yet. Please ask your administrator.']);
        }

        $state = Str::random(40);
        cache()->put("ig_oauth_{$state}", [
            'tenant_id'  => $this->tenantId(),
            'app_id'     => $appId,
            'app_secret' => $appSecret,
        ], now()->addMinutes(10));

        return response()->json([
            'success' => true,
            'state'   => $state,
            'url'     => route('instagram.oauth.start', ['state' => $state]),
        ]);
    }

    // ── OAuth — Start (public — phone browser) ────────────────────
    public function oauthStart(Request $request): RedirectResponse|Response
    {
        $state = $request->query('state');
        $data  = cache("ig_oauth_{$state}");

        if (!$data) {
            return response('QR code has expired. Please generate a new one in the CRM.', 400);
        }

        $scope = implode(',', [
            'pages_show_list',
            'instagram_basic',
            'instagram_manage_messages',
            'instagram_manage_comments',
            'pages_read_engagement',
            'pages_manage_metadata',
        ]);

        $metaUrl = 'https://www.facebook.com/v19.0/dialog/oauth?' . http_build_query([
            'client_id'     => $data['app_id'],
            'redirect_uri'  => route('instagram.oauth.callback'),
            'state'         => $state,
            'scope'         => $scope,
            'response_type' => 'code',
        ]);

        return redirect($metaUrl);
    }

    // ── OAuth — Callback (public — Meta redirects here) ──────────
    public function oauthCallback(Request $request): View|Response
    {
        $state = $request->query('state');
        $code  = $request->query('code');
        $data  = cache("ig_oauth_{$state}");

        if ($request->query('error')) {
            $message = $request->query('error_description', 'Authorization denied.');
            $this->logOauthFailure($data['tenant_id'] ?? null, $message, $request->query(), $state);

            return view('tenant.instagram.oauth_result', [
                'success' => false,
                'message' => $message,
            ]);
        }

        if (!$data) {
            $this->logOauthFailure(null, 'QR code expired or state not found when Meta redirected back.', ['state' => $state], $state);

            return view('tenant.instagram.oauth_result', [
                'success' => false,
                'message' => 'QR code expired. Please generate a new one.',
            ]);
        }

        $debugContext = ['state' => $state];

        try {
            $callbackUrl = route('instagram.oauth.callback');

            $tokenRes = Http::get('https://graph.facebook.com/v19.0/oauth/access_token', [
                'client_id'     => $data['app_id'],
                'client_secret' => $data['app_secret'],
                'redirect_uri'  => $callbackUrl,
                'code'          => $code,
            ]);

            $tokenJson = $tokenRes->json();

            if (!$tokenRes->successful() || empty($tokenJson['access_token'])) {
                throw new \Exception($tokenJson['error']['message'] ?? ('Failed to get access token (HTTP ' . $tokenRes->status() . ').'));
            }

            $longRes = Http::get('https://graph.facebook.com/v19.0/oauth/access_token', [
                'grant_type'        => 'fb_exchange_token',
                'client_id'         => $data['app_id'],
                'client_secret'     => $data['app_secret'],
                'fb_exchange_token' => $tokenJson['access_token'],
            ])->json();

            $longToken = $longRes['access_token'] ?? $tokenJson['access_token'];
            $debugContext['long_token_exchange'] = $this->redactTokens($longRes);

            $pagesRes = Http::get('https://graph.facebook.com/v19.0/me/accounts', [
                'access_token' => $longToken,
            ]);

            $pagesJson = $pagesRes->json();
            $debugContext['pages_response'] = $this->redactTokens($pagesJson);

            if (!$pagesRes->successful()) {
                throw new \Exception($pagesJson['error']['message'] ?? ('Failed to fetch Facebook Pages (HTTP ' . $pagesRes->status() . ').'));
            }

            if (empty($pagesJson['data'])) {
                throw new \Exception('No Facebook Pages found. Link a Page to your Instagram Business account first.');
            }

            // Resolve the linked Instagram Business Account for every Page the
            // user manages — a user can admin more than one Page, and each may
            // have a different Instagram account attached.
            $candidates = [];
            foreach ($pagesJson['data'] as $page) {
                $igRes = Http::get("https://graph.facebook.com/v19.0/{$page['id']}", [
                    'fields'       => 'instagram_business_account',
                    'access_token' => $page['access_token'],
                ])->json();

                $igAccountId = $igRes['instagram_business_account']['id'] ?? null;

                if ($igAccountId) {
                    $candidates[] = [
                        'page_id'              => $page['id'],
                        'page_name'            => $page['name'] ?? $page['id'],
                        'page_token'           => $page['access_token'],
                        'instagram_account_id' => $igAccountId,
                    ];
                }
            }

            if (empty($candidates)) {
                throw new \Exception('No Instagram Business Account linked to any of your Facebook Pages.');
            }

            // More than one eligible Page → let the user pick which one to connect
            // instead of silently binding whichever Meta returned first.
            if (count($candidates) > 1) {
                cache()->put("ig_oauth_pages_{$state}", $candidates, now()->addMinutes(10));

                return view('tenant.instagram.oauth_select_page', [
                    'state' => $state,
                    'pages' => $candidates,
                ]);
            }

            $this->persistInstagramConnection($data['tenant_id'], $candidates[0]);

            cache()->put("ig_oauth_done_{$state}", true, now()->addMinutes(5));
            cache()->forget("ig_oauth_{$state}");

            $this->logOauthSuccess($data['tenant_id'], $candidates[0]);

            return view('tenant.instagram.oauth_result', [
                'success' => true,
                'message' => 'Instagram account connected! You can close this window.',
            ]);
        } catch (\Throwable $e) {
            $this->logOauthFailure($data['tenant_id'] ?? null, $e->getMessage(), $debugContext, $state);

            return view('tenant.instagram.oauth_result', [
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    // ── OAuth — Select Page (public — phone browser, only when the user
    //    manages more than one eligible Facebook Page) ─────────────────
    public function oauthSelectPage(Request $request): View
    {
        $state  = $request->query('state');
        $pageId = $request->query('page_id');

        $data       = cache("ig_oauth_{$state}");
        $candidates = cache("ig_oauth_pages_{$state}");

        if (!$data || !$candidates) {
            $this->logOauthFailure(null, 'Page selection link expired before the user picked a Page.', ['state' => $state], $state);

            return view('tenant.instagram.oauth_result', [
                'success' => false,
                'message' => 'This selection link has expired. Please generate a new QR code.',
            ]);
        }

        $chosen = collect($candidates)->firstWhere('page_id', $pageId);

        if (!$chosen) {
            $this->logOauthFailure($data['tenant_id'], 'Selected page_id did not match any cached candidate.', ['state' => $state, 'page_id' => $pageId], $state);

            return view('tenant.instagram.oauth_result', [
                'success' => false,
                'message' => 'Invalid page selection.',
            ]);
        }

        $this->persistInstagramConnection($data['tenant_id'], $chosen);

        cache()->put("ig_oauth_done_{$state}", true, now()->addMinutes(5));
        cache()->forget("ig_oauth_{$state}");
        cache()->forget("ig_oauth_pages_{$state}");

        $this->logOauthSuccess($data['tenant_id'], $chosen);

        return view('tenant.instagram.oauth_result', [
            'success' => true,
            'message' => 'Instagram account connected! You can close this window.',
        ]);
    }

    // ── OAuth — persist the chosen Page/Instagram account for a tenant ──
    private function persistInstagramConnection(int $tenantId, array $chosen): void
    {
        $settings = InstagramSetting::firstOrNew(['tenant_id' => $tenantId]);
        $settings->tenant_id            = $tenantId;
        $settings->access_token         = $chosen['page_token'];
        $settings->page_id              = $chosen['page_id'];
        $settings->instagram_account_id = $chosen['instagram_account_id'];
        $settings->is_connected         = true;
        if (!$settings->webhook_verify_token) {
            $settings->webhook_verify_token = Str::random(32);
        }
        $settings->save();

        // A Page won't deliver any webhook events (messages/comments) until it
        // explicitly subscribes the app to those fields — without this,
        // automations/chatbot flows never fire even with everything else set up.
        InstagramService::forTenant($tenantId)->subscribeWebhook();
    }

    // ── Strip access tokens out of a Graph API response before it gets
    //    written to instagram_logs — logs are viewable in the CRM UI and
    //    must never hold live Facebook/Instagram credentials ────────────
    private function redactTokens(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        unset($value['access_token']);

        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->redactTokens($item);
            }
        }

        return $value;
    }

    // ── OAuth — record a connect attempt so it shows up on the Logs page
    //    instead of only flashing on the phone screen for a few seconds ──
    private function logOauthFailure(?int $tenantId, string $message, array $context = [], ?string $state = null): void
    {
        // Let the desktop tab (which is polling oauthStatus) surface the
        // failure within seconds instead of only waiting out the 10-minute
        // countdown with no explanation.
        if ($state) {
            cache()->put("ig_oauth_failed_{$state}", $message, now()->addMinutes(10));
        }

        if (!$tenantId) {
            return;
        }

        InstagramLog::create([
            'tenant_id'     => $tenantId,
            'event_type'    => 'oauth_connect',
            'status'        => 'failed',
            'error_message' => $message,
            'raw_payload'   => $context,
        ]);
    }

    private function logOauthSuccess(int $tenantId, array $chosen): void
    {
        InstagramLog::create([
            'tenant_id'     => $tenantId,
            'event_type'    => 'oauth_connect',
            'status'        => 'success',
            'outgoing_text' => "Connected page \"{$chosen['page_name']}\" (page_id={$chosen['page_id']}, ig_account={$chosen['instagram_account_id']})",
        ]);
    }

    // ── OAuth — Poll status (authenticated) ───────────────────────
    public function oauthStatus(Request $request): JsonResponse
    {
        $state = $request->query('state');

        if (cache("ig_oauth_done_{$state}")) {
            $settings = InstagramSetting::forTenant($this->tenantId());
            return response()->json([
                'connected'            => true,
                'instagram_account_id' => $settings->instagram_account_id,
                'page_id'              => $settings->page_id,
            ]);
        }

        if ($failMessage = cache("ig_oauth_failed_{$state}")) {
            cache()->forget("ig_oauth_failed_{$state}");

            return response()->json(['connected' => false, 'failed' => true, 'message' => $failMessage]);
        }

        return response()->json(['connected' => false]);
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
