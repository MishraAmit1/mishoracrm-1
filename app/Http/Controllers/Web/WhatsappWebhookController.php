<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\WhatsappLog;
use App\Models\WhatsappSetting;
use App\Services\WhatsappChatbotService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    // ── GET: Meta webhook verification ───────────────────────────
    public function verify(Request $request)
    {
        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode !== 'subscribe') {
            return response('Invalid mode', 403);
        }

        $setting = WhatsappSetting::where('webhook_verify_token', $token)->first();
        if (!$setting) {
            return response('Token mismatch', 403);
        }

        return response($challenge, 200)->header('Content-Type', 'text/plain');
    }

    // ── POST: Incoming WhatsApp Cloud API events ─────────────────
    public function handle(Request $request): Response
    {
        $payload = $request->all();

        if (($payload['object'] ?? '') !== 'whatsapp_business_account') {
            return response('ok', 200);
        }

        foreach ($payload['entry'] ?? [] as $entry) {
            $wabaId = $entry['id'] ?? null;

            $setting = WhatsappSetting::where('waba_id', $wabaId)->first();
            if (!$setting) continue;

            foreach ($entry['changes'] ?? [] as $change) {
                if (($change['field'] ?? '') !== 'messages') continue;

                $value = $change['value'] ?? [];

                foreach ($value['messages'] ?? [] as $message) {
                    $this->processMessage($setting, $message, $value['contacts'] ?? []);
                }
            }
        }

        return response('EVENT_RECEIVED', 200);
    }

    private function processMessage(WhatsappSetting $setting, array $message, array $contacts): void
    {
        $messageType = $message['type'] ?? 'unknown';
        $waId        = $message['from'] ?? null;
        $messageId   = $message['id'] ?? null;

        if (!$waId) return;

        $contactName = null;
        foreach ($contacts as $contact) {
            if (($contact['wa_id'] ?? '') === $waId) {
                $contactName = $contact['profile']['name'] ?? null;
                break;
            }
        }

        $messageText = null;
        if ($messageType === 'text') {
            $messageText = $message['text']['body'] ?? null;
        }

        // Log incoming message
        try {
            WhatsappLog::create([
                'tenant_id' => $setting->tenant_id,
                'to_phone'  => $waId,
                'to_name'   => $contactName,
                'message'   => $messageText ?? "[{$messageType}]",
                'status'    => 'received',
                'sent_at'   => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('WA log create failed', ['error' => $e->getMessage()]);
        }

        // Chatbot processing
        if ($messageText && $setting->chatbot_enabled) {
            try {
                $service = new WhatsappChatbotService($setting);
                $service->handleIncomingMessage($waId, $messageText, $contactName);
            } catch (\Throwable $e) {
                Log::error('WhatsApp chatbot processing failed', ['error' => $e->getMessage()]);
            }
        }
    }
}
