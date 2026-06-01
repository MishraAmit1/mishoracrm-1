<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class N8nService
{
    // Trigger an n8n webhook with payload
    public function trigger(string $webhookUrl, array $payload): bool
    {
        if (empty($webhookUrl)) return false;

        try {
            $response = Http::timeout(5)->post($webhookUrl, $payload);

            if ($response->failed()) {
                Log::warning('n8n webhook call failed', [
                    'url'    => $webhookUrl,
                    'status' => $response->status(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('n8n webhook exception', [
                'url'   => $webhookUrl,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
