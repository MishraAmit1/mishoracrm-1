<?php

namespace App\Services;

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

    // Process incoming WhatsApp message → find matching flow → send reply
    public function handleIncomingMessage(string $waId, string $messageText, ?string $contactName = null): bool
    {
        if (!$this->settings->chatbot_enabled) return false;

        $session = WhatsappChatbotSession::getOrCreate($this->settings->tenant_id, $waId, $contactName);
        $session->update(['last_message_at' => now()]);

        // Find matching flow
        $flows = WhatsappChatbotFlow::where('tenant_id', $this->settings->tenant_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('is_default') // non-default first
            ->get();

        $matchedFlow = null;
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

        if (!$matchedFlow) return false;

        $matchedFlow->incrementTriggered();

        return $this->sendMessage($waId, $matchedFlow->response_message);
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
