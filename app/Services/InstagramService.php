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

    // Get recent posts/media for the automation post picker
    public function getRecentMedia(int $limit = 25): array
    {
        $response = Http::get(self::GRAPH_URL . '/' . $this->settings->instagram_account_id . '/media', [
            'fields'       => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp',
            'limit'        => $limit,
            'access_token' => $this->settings->access_token,
        ]);

        if ($response->failed()) {
            Log::error('Instagram media fetch failed', [
                'tenant_id' => $this->settings->tenant_id,
                'error'     => $response->json(),
            ]);
            return [];
        }

        return $response->json('data') ?? [];
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
        return $this->subscribeWebhookDetailed()['success'];
    }

    // Same as subscribeWebhook() but returns the raw Graph API response too,
    // so callers can surface the actual error instead of a flat true/false.
    public function subscribeWebhookDetailed(): array
    {
        $response = Http::post(self::GRAPH_URL . '/' . $this->settings->page_id . '/subscribed_apps', [
            'subscribed_fields' => 'messages,comments,mentions',
            'access_token'      => $this->settings->access_token,
        ]);

        return [
            'success' => $response->successful(),
            'status'  => $response->status(),
            'body'    => $response->json(),
        ];
    }

    // Verify token matches our stored token
    public function verifyWebhookToken(string $token): bool
    {
        return $token === $this->settings->webhook_verify_token;
    }
}
