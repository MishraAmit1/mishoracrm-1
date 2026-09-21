<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Services\LoyaltyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// Per-tenant API-key protected loyalty lookup — the tenant's own backend
// calls this (having done its own identity check) to embed a rewards widget
// on their site. The API key IS the trust boundary; keep it server-side.
class LoyaltyController extends Controller
{
    public function lookup(Request $request): JsonResponse
    {
        $tenant = auth()->user()?->tenant ?? app('tenant');

        if (!$tenant || !$tenant->hasModuleEnabled('loyalty')) {
            return response()->json(['success' => false, 'message' => 'Loyalty module is not enabled.'], 403);
        }

        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:150'],
        ]);

        $identifier = trim($data['identifier']);
        $q          = Contact::query(); // api.key middleware scopes to the tenant

        if (str_contains($identifier, '@')) {
            $contact = $q->where('email', $identifier)->first();
        } else {
            $digits = preg_replace('/\D/', '', $identifier);
            $last10 = strlen($digits) >= 10 ? substr($digits, -10) : $digits;
            $contact = $q->where('phone_normalized', $last10)->first();
        }

        if (!$contact) {
            return response()->json(['success' => false, 'message' => 'No loyalty account found for that identifier.'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => app(LoyaltyService::class)->snapshot($contact),
        ]);
    }
}
