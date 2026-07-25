<?php

namespace App\Services;

use App\Models\TenantSlackConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackService
{
    public static function send(int $tenantId, string $title, string $message, ?string $url = null): void
    {
        $config = TenantSlackConfig::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();

        if (!$config) {
            return;
        }

        try {
            static::post($config->webhook_url, $title, $message, $url);
        } catch (\Exception $e) {
            // already logged in post()
        }
    }

    public static function test(string $webhookUrl): array
    {
        try {
            $res = static::post(
                $webhookUrl,
                'Test Notification',
                'This is a test ping from your CRM. If you can see this in Slack, notifications are wired up correctly. ✅'
            );
            return ['success' => $res->successful(), 'status' => $res->status()];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private static function post(string $webhookUrl, string $title, string $message, ?string $url = null)
    {
        $text = "*{$title}*\n{$message}";
        if ($url) {
            $text .= "\n<{$url}|View in CRM>";
        }

        try {
            return Http::timeout(5)->post($webhookUrl, ['text' => $text]);
        } catch (\Exception $e) {
            Log::warning("Slack notification failed: {$e->getMessage()}");
            throw $e;
        }
    }
}
