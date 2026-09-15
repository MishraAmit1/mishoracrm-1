<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Tenant;
use App\Models\WhatsappChatbotFlow;
use App\Models\WhatsappChatbotSession;
use App\Models\WhatsappSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappChatbotService
{
    private const GRAPH_URL = 'https://graph.facebook.com/v21.0';

    private WhatsappSetting $settings;

    public function __construct(WhatsappSetting $settings)
    {
        $this->settings = $settings;
    }

    public static function forTenant(int $tenantId): self
    {
        $settings = WhatsappSetting::where('tenant_id', $tenantId)->firstOrFail();
        return new self($settings);
    }

    // "Scan the QR → send JOIN" welcome capture (§2a). A first-time number that
    // sends the tenant's welcome keyword is auto-enrolled as a Contact and
    // gifted the welcome bonus. Returns true if it handled the message.
    public function handleLoyaltyWelcome(string $waId, string $messageText, ?string $profileName): bool
    {
        $tenant = Tenant::find($this->settings->tenant_id);
        if (!$tenant || !$tenant->hasModuleEnabled('loyalty')) {
            return false;
        }

        $s     = $tenant->loyaltySettings();
        $bonus = (int) $s['welcome_bonus_points'];
        if ($bonus <= 0) {
            return false;
        }

        $keyword = strtolower(trim((string) ($s['welcome_keyword'] ?: 'JOIN')));
        $text    = strtolower(trim($messageText));
        if ($text !== $keyword && !str_starts_with($text, $keyword . ' ')) {
            return false;
        }

        if ($contact = $this->findContactByWaId($tenant, $waId)) {
            // Already known — don't re-gift, just acknowledge.
            return $this->sendMessage($waId, "You're already a {$tenant->name} member, {$contact->name}! You have "
                . number_format((int) $contact->loyalty_points) . ' points.');
        }

        $contact = $this->enrolFromWhatsapp($tenant, $waId, $profileName);

        $message = strtr($s['welcome_message'] ?: Tenant::LOYALTY_DEFAULTS['welcome_message'], [
            '{{contact_name}}' => $contact->name,
            '{{points}}'       => number_format($bonus),
            '{{tenant_name}}'  => $tenant->name,
        ]);

        return $this->sendMessage($waId, $message);
    }

    // Match a Contact by the last 10 digits of a WhatsApp id, ignoring
    // spaces / dashes / + in the stored phone.
    public function findContactByWaId(Tenant $tenant, string $waId): ?Contact
    {
        $digits = preg_replace('/\D/', '', $waId);
        $last10 = strlen($digits) >= 10 ? substr($digits, -10) : $digits;

        return Contact::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->whereRaw("RIGHT(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), '(', ''), 10) = ?", [$last10])
            ->first();
    }

    // Create a Contact for a WhatsApp number and grant the welcome bonus (if
    // one is configured). Returns the (possibly pre-existing) Contact.
    public function enrolFromWhatsapp(Tenant $tenant, string $waId, ?string $profileName): Contact
    {
        $contact = $this->findContactByWaId($tenant, $waId);
        if ($contact) {
            return $contact;
        }

        $contact = Contact::create([
            'tenant_id' => $tenant->id,
            'name'      => $profileName ?: 'WhatsApp Customer',
            'phone'     => $waId,
        ]);

        $bonus = (int) $tenant->loyaltySettings()['welcome_bonus_points'];
        if ($bonus > 0) {
            app(\App\Services\LoyaltyService::class)->manualAdjust($contact, $bonus, 'Welcome bonus (WhatsApp join)');
        }

        return $contact->refresh();
    }

    // Swap {{loyalty_*}} / {{contact_name}} / {{tenant_name}} placeholders in a
    // chatbot response for the sender's real data. Non-loyalty flows are
    // untouched (no placeholders → no change).
    public function resolveMessage(string $message, ?Tenant $tenant, string $waId): string
    {
        if (!str_contains($message, '{{')) {
            return $message;
        }

        $contact = $tenant ? $this->findContactByWaId($tenant, $waId) : null;
        $s       = $tenant?->loyaltySettings() ?? Tenant::LOYALTY_DEFAULTS;
        $block   = (int) $s['redeem_points_block'];
        $points  = (int) ($contact->loyalty_points ?? 0);
        $value   = $block > 0 ? floor($points / $block) * (float) $s['redeem_value'] : 0;

        return strtr($message, [
            '{{contact_name}}'       => $contact->name ?? 'there',
            '{{tenant_name}}'        => $tenant->name ?? '',
            '{{loyalty_points}}'     => number_format($points),
            '{{loyalty_lifetime}}'   => number_format((int) ($contact->loyalty_lifetime_points ?? 0)),
            '{{loyalty_tier}}'       => $contact?->loyaltyTierLabel() ?? '—',
            '{{loyalty_redeemable}}' => '₹' . number_format($value, 0),
        ]);
    }

    // Loyalty self-check: a customer texts "points" / "balance" / "rewards" and
    // gets their balance back. Independent of the chatbot flow engine — works
    // whenever the tenant has the Loyalty module + settings['loyalty']
    // ['whatsapp_self_check'] on. Returns true if it answered the message.
    public function handleLoyaltyKeyword(string $waId, string $messageText): bool
    {
        $tenant = Tenant::find($this->settings->tenant_id);
        if (!$tenant || !$tenant->hasModuleEnabled('loyalty')) {
            return false;
        }
        if (!($tenant->loyaltySettings()['whatsapp_self_check'] ?? false)) {
            return false;
        }

        $text = strtolower(trim($messageText));
        $hit  = false;
        foreach (['points', 'balance', 'rewards', 'loyalty'] as $kw) {
            if ($text === $kw || str_starts_with($text, $kw . ' ') || str_starts_with($text, 'my ' . $kw)) {
                $hit = true;
                break;
            }
        }
        if (!$hit) {
            return false;
        }

        $contact = $this->findContactByWaId($tenant, $waId);

        if (!$contact) {
            return $this->sendMessage($waId, "We couldn't find a loyalty account for this number. Please ask our staff to add you on your next visit!");
        }

        $s     = $tenant->loyaltySettings();
        $block = (int) $s['redeem_points_block'];
        $value = $block > 0 ? floor((int) $contact->loyalty_points / $block) * (float) $s['redeem_value'] : 0;
        $tier  = $contact->loyaltyTierLabel();

        $reply = "Hi {$contact->name}! 🎁\n"
            . 'Loyalty points: ' . number_format((int) $contact->loyalty_points) . ($tier ? " ({$tier})" : '') . "\n"
            . ($value > 0
                ? 'Worth up to ₹' . number_format($value, 0) . " off your next bill at {$tenant->name}."
                : "Keep visiting {$tenant->name} to earn rewards!");

        return $this->sendMessage($waId, $reply);
    }

    // Single entry point for every inbound WhatsApp text. Loyalty's built-in
    // replies (self-check + QR "join") run first — they have their own opt-in
    // switches and work even if the tenant hasn't enabled the full chatbot —
    // then the tenant's own chatbot flows.
    // $buttonId is set only when the inbound message is a tap on a quick-reply
    // button (see WhatsappWebhookController) — when that button was explicitly
    // linked to a next flow (via the "next_flow_id" field in Quick Reply
    // Buttons), we jump straight there instead of keyword-matching $messageText.
    public function handleIncomingMessage(string $waId, string $messageText, ?string $contactName = null, ?string $buttonId = null): bool
    {
        $tenant = Tenant::find($this->settings->tenant_id);

        if ($tenant && $tenant->hasModuleEnabled('loyalty')) {
            if ($this->handleLoyaltyWelcome($waId, $messageText, $contactName)) {
                return true;
            }
            if ($this->handleLoyaltyKeyword($waId, $messageText)) {
                return true;
            }
        }

        if (!$this->settings->chatbot_enabled) return false;

        $session = WhatsappChatbotSession::getOrCreate($this->settings->tenant_id, $waId, $contactName);
        $session->update(['last_message_at' => now()]);

        $matchedFlow = null;

        // Direct jump — the tapped button was linked to a specific next flow.
        if ($buttonId && str_starts_with($buttonId, 'flow_')) {
            $matchedFlow = WhatsappChatbotFlow::where('tenant_id', $this->settings->tenant_id)
                ->where('id', (int) substr($buttonId, 5))
                ->where('is_active', true)
                ->first();
        }

        // Otherwise — keyword match against the typed text / button title, same as before.
        if (!$matchedFlow) {
            $flows = WhatsappChatbotFlow::where('tenant_id', $this->settings->tenant_id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('is_default') // non-default first
                ->get();

            foreach ($flows as $flow) {
                if (!$flow->is_default && $flow->matches($messageText)) {
                    $matchedFlow = $flow;
                    break;
                }
            }

            // Fallback to default
            if (!$matchedFlow) {
                $matchedFlow = $flows->firstWhere('is_default', true);
            }
        }

        if (!$matchedFlow) return false;

        $matchedFlow->incrementTriggered();

        // Flow-attached side effect (e.g. enrol the sender in loyalty).
        if ($matchedFlow->action === 'loyalty_join' && $tenant && $tenant->hasModuleEnabled('loyalty')) {
            $this->enrolFromWhatsapp($tenant, $waId, $contactName);
        }

        $body = $this->resolveMessage($matchedFlow->response_message, $tenant, $waId);

        // Flows with quick-reply buttons configured get sent as an interactive
        // message; every other flow keeps sending plain text exactly as before.
        if (!empty($matchedFlow->quick_replies)) {
            return $this->sendInteractiveButtons($waId, $body, $matchedFlow->quick_replies);
        }

        return $this->sendMessage($waId, $body);
    }

    // Send WhatsApp message via Cloud API
    public function sendMessage(string $waId, string $message): bool
    {
        $response = Http::withToken($this->settings->access_token)
            ->post(self::GRAPH_URL . '/' . $this->settings->phone_number_id . '/messages', [
                'messaging_product' => 'whatsapp',
                'recipient_type'    => 'individual',
                'to'                => $waId,
                'type'              => 'text',
                'text'              => ['body' => $message],
            ]);

        if ($response->failed()) {
            Log::error('WhatsApp message failed', [
                'tenant_id' => $this->settings->tenant_id,
                'to'        => $waId,
                'error'     => $response->json(),
            ]);
            return false;
        }

        return true;
    }

    // Send a WhatsApp "reply buttons" interactive message — up to 3 tappable
    // options under $body. Each entry in $buttons is ['title' => string,
    // 'next_flow_id' => int|null]. When a button carries a next_flow_id we
    // encode it into the reply id (flow_{id}) so the webhook can jump straight
    // to that flow; otherwise the button title falls back to normal keyword
    // matching, same as typed text (see handleIncomingMessage).
    public function sendInteractiveButtons(string $waId, string $body, array $buttons): bool
    {
        $buttons = array_slice(array_values(array_filter(
            $buttons,
            fn($b) => trim((string) ($b['title'] ?? '')) !== ''
        )), 0, 3);

        if (empty($buttons)) {
            return $this->sendMessage($waId, $body);
        }

        $response = Http::withToken($this->settings->access_token)
            ->post(self::GRAPH_URL . '/' . $this->settings->phone_number_id . '/messages', [
                'messaging_product' => 'whatsapp',
                'recipient_type'    => 'individual',
                'to'                => $waId,
                'type'              => 'interactive',
                'interactive'       => [
                    'type' => 'button',
                    'body' => ['text' => $body],
                    'action' => [
                        'buttons' => collect($buttons)->values()->map(fn($btn, $i) => [
                            'type'  => 'reply',
                            'reply' => [
                                'id'    => !empty($btn['next_flow_id']) ? 'flow_' . $btn['next_flow_id'] : 'kw_' . $i,
                                'title' => mb_substr((string) $btn['title'], 0, 20),
                            ],
                        ])->all(),
                    ],
                ],
            ]);

        if ($response->failed()) {
            Log::error('WhatsApp interactive message failed', [
                'tenant_id' => $this->settings->tenant_id,
                'to'        => $waId,
                'error'     => $response->json(),
            ]);
            return false;
        }

        return true;
    }

    // Upload a local file to Meta's media endpoint, returns the media id
    // (or null on failure) — required before a media message can reference it.
    public function uploadMedia(string $filePath, string $mimeType): ?string
    {
        $response = Http::withToken($this->settings->access_token)
            ->attach('file', file_get_contents($filePath), basename($filePath))
            ->post(self::GRAPH_URL . '/' . $this->settings->phone_number_id . '/media', [
                'messaging_product' => 'whatsapp',
                'type'              => $mimeType,
            ]);

        if ($response->failed()) {
            Log::error('WhatsApp media upload failed', [
                'tenant_id' => $this->settings->tenant_id,
                'error'     => $response->json(),
            ]);
            return null;
        }

        return $response->json('id');
    }

    // Send an image/document message referencing an already-uploaded media id.
    public function sendMediaMessage(string $waId, string $mediaId, string $type, ?string $caption = null, ?string $filename = null): bool
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $waId,
            'type'              => $type,
        ];

        $payload[$type] = $type === 'document'
            ? array_filter(['id' => $mediaId, 'filename' => $filename, 'caption' => $caption])
            : array_filter(['id' => $mediaId, 'caption' => $caption]);

        $response = Http::withToken($this->settings->access_token)
            ->post(self::GRAPH_URL . '/' . $this->settings->phone_number_id . '/messages', $payload);

        if ($response->failed()) {
            Log::error('WhatsApp media message failed', [
                'tenant_id' => $this->settings->tenant_id,
                'to'        => $waId,
                'error'     => $response->json(),
            ]);
            return false;
        }

        return true;
    }

    // WhatsApp Cloud API only distinguishes "image" from "document" for the
    // media types this app allows sending (jpg/png vs pdf/doc/xls etc).
    public static function mediaTypeForMime(string $mime): string
    {
        return str_starts_with($mime, 'image/') ? 'image' : 'document';
    }

    // Verify webhook token
    public function verifyWebhookToken(string $token): bool
    {
        return $token === $this->settings->webhook_verify_token;
    }

    // Get WhatsApp Business Account info
    public function getAccountInfo(): ?array
    {
        $response = Http::withToken($this->settings->access_token)
            ->get(self::GRAPH_URL . '/' . $this->settings->phone_number_id, [
                'fields' => 'id,display_phone_number,verified_name,quality_rating',
            ]);

        if ($response->failed()) return null;

        return $response->json();
    }
}
