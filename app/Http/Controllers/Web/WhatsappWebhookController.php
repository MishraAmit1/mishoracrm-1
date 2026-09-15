<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use App\Models\WhatsappLog;
use App\Models\WhatsappSetting;
use App\Services\QuotationService;
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
        $buttonId    = null;
        if ($messageType === 'text') {
            $messageText = $message['text']['body'] ?? null;
        } elseif ($messageType === 'interactive') {
            // Tapping a reply button/list option feeds its title back in as
            // if the user had typed it (keyword-matching fallback); if the
            // button was explicitly linked to a next flow, $buttonId lets the
            // chatbot service jump straight there instead.
            $interactiveType = $message['interactive']['type'] ?? null;
            if ($interactiveType === 'button_reply') {
                $messageText = $message['interactive']['button_reply']['title'] ?? null;
                $buttonId    = $message['interactive']['button_reply']['id'] ?? null;
            } elseif ($interactiveType === 'list_reply') {
                $messageText = $message['interactive']['list_reply']['title'] ?? null;
                $buttonId    = $message['interactive']['list_reply']['id'] ?? null;
            }
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

        // A tap on a quotation Accept/Reject button sent from QuotationController@sendWhatsapp —
        // handled directly here (not via the chatbot flow engine) since it acts on the
        // quotation itself rather than replying with a message.
        if ($buttonId && (str_starts_with($buttonId, 'qacc_') || str_starts_with($buttonId, 'qrej_'))) {
            $this->handleQuotationResponse($setting, $waId, $buttonId);
            return;
        }

        // Inbound message handling — the chatbot service runs loyalty's built-in
        // replies first, then the tenant's own flows.
        if ($messageText) {
            try {
                (new WhatsappChatbotService($setting))->handleIncomingMessage($waId, $messageText, $contactName, $buttonId);
            } catch (\Throwable $e) {
                Log::error('WhatsApp inbound processing failed', ['error' => $e->getMessage()]);
            }
        }
    }

    // Mirrors the accept/reject rules in Public\QuotationController — no e-sign
    // is possible from a WhatsApp button tap, so acceptance is recorded with the
    // contact's own name as the signed name instead of a captured signature.
    private function handleQuotationResponse(WhatsappSetting $setting, string $waId, string $buttonId): void
    {
        $accept = str_starts_with($buttonId, 'qacc_');
        $token  = substr($buttonId, 5);

        $service = new WhatsappChatbotService($setting);

        $quotation = Quotation::withoutGlobalScope('tenant')
            ->where('public_token', $token)
            ->where('tenant_id', $setting->tenant_id)
            ->first();

        if (!$quotation) {
            $service->sendMessage($waId, 'Sorry, this quotation could not be found.');
            return;
        }

        if ($quotation->hasCustomerResponded()) {
            $service->sendMessage($waId, 'This quotation has already been responded to.');
            return;
        }

        if ($quotation->isExpired()) {
            $service->sendMessage($waId, 'This quotation has expired and can no longer be accepted.');
            return;
        }

        if ($accept) {
            $quotation->update([
                'status'                => 'accepted',
                'signed_name'           => $quotation->contact?->name ?: 'Accepted via WhatsApp',
                'customer_responded_at' => now(),
            ]);
            QuotationService::accept($quotation);
            $service->sendMessage($waId, "Thank you! Quotation {$quotation->number} has been accepted.");
        } else {
            $quotation->update([
                'status'                => 'rejected',
                'customer_responded_at' => now(),
            ]);
            $service->sendMessage($waId, "Quotation {$quotation->number} has been marked as rejected.");
        }
    }
}
