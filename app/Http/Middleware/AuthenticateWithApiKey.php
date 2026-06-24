<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateWithApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $rawKey = $this->extractKey($request);

        if (!$rawKey) {
            return response()->json([
                'success' => false,
                'message' => 'API key missing. Send it as X-API-Key header, Authorization: Bearer <key>, or ?api_key= query param.',
            ], 401);
        }

        $apiKey = ApiKey::where('key', $rawKey)->with('tenant')->first();

        if (!$apiKey || !$apiKey->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or inactive API key.',
            ], 401);
        }

        // Bind tenant context — same pattern as IdentifyTenant middleware
        app()->instance('tenant',    $apiKey->tenant);
        app()->instance('tenant_id', $apiKey->tenant_id);

        $apiKey->touchLastUsed();

        return $next($request);
    }

    private function extractKey(Request $request): ?string
    {
        // 1. X-API-Key header (preferred)
        if ($key = $request->header('X-API-Key')) {
            return $key;
        }

        // 2. Authorization: Bearer <key>  (n8n / common REST clients)
        $auth = $request->header('Authorization', '');
        if (str_starts_with($auth, 'Bearer ')) {
            $token = substr($auth, 7);
            // Only treat as API key if it starts with our prefix
            if (str_starts_with($token, 'crm_')) {
                return $token;
            }
        }

        // 3. ?api_key= query param (webhooks / simple integrations)
        if ($key = $request->query('api_key')) {
            return $key;
        }

        return null;
    }
}
