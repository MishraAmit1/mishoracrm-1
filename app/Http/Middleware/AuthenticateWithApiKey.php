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
        // Accept key from header (preferred) or query param
        $rawKey = $request->header('X-API-Key') ?? $request->query('api_key');

        if (!$rawKey) {
            return response()->json([
                'success' => false,
                'message' => 'API key missing. Pass it as X-API-Key header.',
            ], 401);
        }

        $apiKey = ApiKey::where('key', $rawKey)->first();

        if (!$apiKey || !$apiKey->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or inactive API key.',
            ], 401);
        }

        // Bind tenant context — same pattern as IdentifyTenant middleware
        app()->instance('tenant', $apiKey->tenant);
        app()->instance('tenant_id', $apiKey->tenant_id);

        // Update last used time without touching updated_at
        $apiKey->touchLastUsed();

        return $next($request);
    }
}
