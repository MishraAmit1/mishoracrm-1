<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\TenantIntegration;
use App\Services\Integrations\MetaLeadService;
use App\Services\Integrations\JustDialLeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Public webhook endpoint for all Lead source integrations.
 *
 * Routes (no auth, no CSRF):
 *   GET  /webhook/leads/{token}   → webhook verification (Meta uses GET)
 *   POST /webhook/leads/{token}   → incoming lead data (all platforms)
 *
 * The {token} is unique per TenantIntegration row.
 */
class LeadWebhookController extends Controller
{
    // ── GET: Webhook Verification ─────────────────────────────────
    // Used by Meta to verify the webhook endpoint

    public function verify(Request $request, string $token): Response
    {
        $integration = TenantIntegration::where('webhook_token', $token)
            ->where('is_active', true)
            ->first();

        if (!$integration) {
            return response('Not Found', 404);
        }

        if ($integration->platform === 'meta_lead_ads') {
            $service   = MetaLeadService::forIntegration($integration);
            $challenge = $service->verifyWebhook(
                $request->query('hub_mode',         ''),
                $request->query('hub_verify_token', ''),
                $request->query('hub_challenge',    '')
            );

            if ($challenge === false) {
                Log::warning("Meta webhook verify failed for token {$token}");
                return response('Forbidden', 403);
            }

            return response($challenge, 200);
        }

        return response('OK', 200);
    }

    // ── POST: Incoming Lead Data ───────────────────────────────────

    public function handle(Request $request, string $token): JsonResponse
    {
        $integration = TenantIntegration::where('webhook_token', $token)
            ->where('is_active', true)
            ->first();

        if (!$integration) {
            return response()->json(['error' => 'Not found'], 404);
        }

        try {
            return match ($integration->platform) {
                'meta_lead_ads' => $this->handleMeta($request, $integration),
                'justdial'      => $this->handleJustDial($request, $integration),
                'tradeindia'    => $this->handleGenericWebhook($request, $integration),
                'sulekha'       => $this->handleGenericWebhook($request, $integration),
                default         => response()->json(['ok' => true]),
            };
        } catch (\Throwable $e) {
            Log::error("Lead webhook error [{$integration->platform}] tenant {$integration->tenant_id}", [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Internal error'], 500);
        }
    }

    // ── Meta Lead Ads ─────────────────────────────────────────────

    private function handleMeta(Request $request, TenantIntegration $integration): JsonResponse
    {
        $service = MetaLeadService::forIntegration($integration);

        // Verify X-Hub-Signature-256 header
        $signature = $request->header('X-Hub-Signature-256', '');
        if (!$service->verifySignature($request->getContent(), $signature)) {
            Log::warning("Meta webhook: invalid signature for tenant {$integration->tenant_id}");
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $imported = $service->processWebhook($request->all());

        Log::info("Meta Lead Ads: imported {$imported} leads for tenant {$integration->tenant_id}");

        return response()->json(['ok' => true, 'imported' => $imported]);
    }

    // ── JustDial ──────────────────────────────────────────────────

    private function handleJustDial(Request $request, TenantIntegration $integration): JsonResponse
    {
        $service = JustDialLeadService::forIntegration($integration);

        // JustDial may send a key in header or query param
        $providedKey = $request->header('X-JustDial-Key', $request->query('key', ''));
        if (!$service->verifyRequest($providedKey)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        $created = $service->processWebhook($payload);

        return response()->json(['ok' => true, 'created' => $created]);
    }

    // ── Generic (TradeIndia, Sulekha, etc.) ───────────────────────
    // These platforms have similar form-POST lead structures

    private function handleGenericWebhook(Request $request, TenantIntegration $integration): JsonResponse
    {
        $data = $request->all();

        $name  = $data['name']    ?? $data['full_name']    ?? $data['contact_name'] ?? 'Lead';
        $phone = $data['phone']   ?? $data['mobile']       ?? $data['contact']      ?? null;
        $email = $data['email']   ?? $data['email_id']     ?? null;
        $city  = $data['city']    ?? $data['location']     ?? null;
        $msg   = $data['message'] ?? $data['requirements'] ?? $data['query']         ?? null;

        if (!$name && !$phone && !$email) {
            return response()->json(['ok' => true, 'skipped' => 'no_data']);
        }

        \App\Models\Lead::create([
            'tenant_id'  => $integration->tenant_id,
            'name'       => $name,
            'phone'      => $phone,
            'email'      => $email,
            'city'       => $city,
            'source'     => $integration->platform,
            'status'     => 'new',
            'priority'   => 'medium',
            'notes'      => $msg,
            'created_by' => null,
        ]);

        $integration->increment('leads_imported');
        $integration->update(['last_synced_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
