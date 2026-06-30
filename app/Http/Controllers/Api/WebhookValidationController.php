<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookValidationController extends Controller
{
    /**
     * n8n is webhook token ko validate karta hai.
     *
     * POST /api/v1/webhook/validate
     * Body: { "token": "crm_whk_xxxxxxxxxx" }
     *
     * Response (valid):   { "valid": true,  "tenant_id": 5, "tenant_name": "Ramesh Traders" }
     * Response (invalid): { "valid": false }
     */
    public function validate(Request $request): JsonResponse
    {
        $token = $request->input('token')
            ?? $request->header('X-CRM-Token');

        if (empty($token)) {
            return response()->json(['valid' => false, 'message' => 'Token missing'], 401);
        }

        $tenant = Tenant::whereJsonContains('settings->webhook_token', $token)->first();

        if (! $tenant) {
            return response()->json(['valid' => false], 401);
        }

        return response()->json([
            'valid'       => true,
            'tenant_id'   => $tenant->id,
            'tenant_name' => $tenant->name,
        ]);
    }
}
