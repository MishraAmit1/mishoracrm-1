<?php

namespace App\Services;

use App\Models\InstagramSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstagramService
{
    private const GRAPH_URL = 'https://graph.facebook.com/v21.0';

    private InstagramSetting $settings;

    public function __construct(InstagramSetting $settings)
    {
        $this->settings = $settings;
    }

    public static function forTenant(int $tenantId): self
    {
        $settings = InstagramSetting::where('tenant_id', $tenantId)->firstOrFail();
        return new self($settings);
    }

    // Send DM to an Instagram user
    public function sendDm(string $recipientIgId, string $message): bool
    {
        $response = Http::post(self::GRAPH_URL . '/' . $this->settings->instagram_account_id . '/messages', [
            'recipient'          => ['id' => $recipientIgId],
            'message'            => ['text' => $message],
            'access_token'       => $this->settings->access_token,
            'messaging_type'     => 'RESPONSE',
        ]);

        if ($response->failed()) {
            Log::error('Instagram DM failed', [
                'tenant_id' => $this->settings->tenant_id,
                'recipient' => $recipientIgId,
                'error'     => $response->json(),
            ]);
            return false;
        }

        return true;
    }

    // Reply to a comment on a post
    public function replyToComment(string $commentId, string $message): bool
    {
        $response = Http::post(self::GRAPH_URL . '/' . $commentId . '/replies', [
            'message'      => $message,
            'access_token' => $this->settings->access_token,
        ]);

        if ($response->failed()) {
            Log::error('Instagram comment reply failed', [
                'tenant_id'  => $this->settings->tenant_id,
                'comment_id' => $commentId,
                'error'      => $response->json(),
            ]);
            return false;
        }

        return true;
    }

    // Get Instagram account info
    public function getAccountInfo(): ?array
    {
        $response = Http::get(self::GRAPH_URL . '/' . $this->settings->instagram_account_id, [
            'fields'       => 'id,name,username,followers_count,profile_picture_url',
            'access_token' => $this->settings->access_token,
        ]);

        if ($response->failed()) return null;

        return $response->json();
    }

    // Subscribe page to webhooks
    public function subscribeWebhook(): bool
    {
        $response = Http::post(self::GRAPH_URL . '/' . $this->settings->page_id . '/subscribed_apps', [
            'subscribed_fields' => 'messages,comments,mention',
            'access_token'      => $this->settings->access_token,
        ]);

        return $response->successful();
    }

    // Verify token matches our stored token
    public function verifyWebhookToken(string $token): bool
    {
        return $token === $this->settings->webhook_verify_token;
    }
}
