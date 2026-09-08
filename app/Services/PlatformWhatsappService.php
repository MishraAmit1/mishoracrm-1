<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Platform-owned WhatsApp sender (Meta Cloud API) used for billing / account
// messages from Milan CRM itself to tenant owners. Separate from the
// per-tenant WhatsApp integration (WhatsappChatbotService) which uses each
// tenant's own connected number. Configured in the superadmin Billing Profile.
class PlatformWhatsappService
{
    private const GRAPH_URL = 'https://graph.facebook.com/v21.0';

    public static function enabled(): bool
    {
        return PlatformSetting::get('platform_wa_enabled') === '1'
            && filled(PlatformSetting::get('platform_wa_phone_number_id'))
            && filled(PlatformSetting::get('platform_wa_access_token'));
    }

    // Best-effort plain-text send. Returns ['ok' => bool, 'error' => ?string].
    // Note: a free-form text only lands if the recipient messaged the number
    // in the last 24h; outside that window Meta needs an approved template.
    public static function sendText(string $toPhone, string $message): array
    {
        if (!static::enabled()) {
            return ['ok' => false, 'error' => 'Platform WhatsApp is not configured.'];
        }

        $waId = preg_replace('/\D/', '', $toPhone);
        if (strlen($waId) === 10) {
            $waId = '91' . $waId;
        }

        try {
            $response = Http::withToken(PlatformSetting::get('platform_wa_access_token'))
                ->asJson()
                ->timeout(15)
                ->post(self::GRAPH_URL . '/' . PlatformSetting::get('platform_wa_phone_number_id') . '/messages', [
                    'messaging_product' => 'whatsapp',
                    'recipient_type'    => 'individual',
                    'to'                => $waId,
                    'type'              => 'text',
                    'text'              => ['preview_url' => true, 'body' => $message],
                ]);

            if ($response->successful()) {
                return ['ok' => true, 'error' => null];
            }

            $error = $response->json('error.message') ?? ('HTTP ' . $response->status());
            Log::warning('Platform WhatsApp send failed: ' . $error);

            return ['ok' => false, 'error' => $error];
        } catch (\Throwable $e) {
            Log::warning('Platform WhatsApp send threw: ' . $e->getMessage());

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
