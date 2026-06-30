<?php

namespace App\Services;

use App\Models\TenantWebhook;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    public static function fire(string $event, int $tenantId, array $data): void
    {
        $webhooks = TenantWebhook::where('tenant_id', $tenantId)
            ->where('event', $event)
            ->where('is_active', true)
            ->get();

        if ($webhooks->isEmpty()) {
            return;
        }

        $tenant = \App\Models\Tenant::find($tenantId);
        $token  = $tenant?->getWebhookToken();

        $payload = [
            'event'     => $event,
            'tenant_id' => $tenantId,
            'crm_token' => $token,
            'timestamp' => now()->toIso8601String(),
            'data'      => $data,
        ];

        foreach ($webhooks as $webhook) {
            try {
                Http::timeout(5)
                    ->withHeader('X-CRM-Token', $token)
                    ->post($webhook->webhook_url, $payload);
            } catch (\Exception $e) {
                Log::warning("Webhook failed [{$event}] tenant:{$tenantId} url:{$webhook->webhook_url} — {$e->getMessage()}");
            }
        }
    }

    public static function test(string $webhookUrl, string $event, int $tenantId): array
    {
        $tenant = \App\Models\Tenant::find($tenantId);
        $token  = $tenant?->getWebhookToken();

        try {
            $res = Http::timeout(5)
                ->withHeader('X-CRM-Token', $token)
                ->post($webhookUrl, [
                    'event'     => $event,
                    'tenant_id' => $tenantId,
                    'crm_token' => $token,
                    'timestamp' => now()->toIso8601String(),
                    'data'      => ['test' => true, 'message' => 'Test ping from CRM'],
                ]);
            return ['success' => true, 'status' => $res->status()];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
