<?php

namespace App\Services\Integrations;

use App\Models\Lead;
use App\Models\TenantIntegration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Meta Lead Ads Integration
 *
 * Flow:
 *   1. Tenant creates a Facebook App → adds Webhooks product
 *   2. Subscribes to "leadgen" field on their Facebook Page
 *   3. Sets webhook URL to: /webhook/leads/{webhook_token}
 *   4. When lead comes → Meta POSTs webhook → we fetch full lead via Graph API
 *   5. Lead saved in CRM with source = 'facebook'
 *
 * Required credentials (stored encrypted in TenantIntegration):
 *   - page_access_token : long-lived Page Access Token
 *   - app_secret        : Facebook App Secret (for webhook signature verification)
 *   - verify_token      : custom string tenant sets in Facebook webhook config
 */
class MetaLeadService
{
    private const GRAPH_URL = 'https://graph.facebook.com/v19.0';

    public function __construct(private TenantIntegration $integration) {}

    public static function forIntegration(TenantIntegration $integration): self
    {
        return new self($integration);
    }

    // ── Webhook Verification (GET) ────────────────────────────────
    // Facebook calls GET with hub.mode, hub.verify_token, hub.challenge
    // We return hub.challenge if verify_token matches

    public function verifyWebhook(string $mode, string $token, string $challenge): string|false
    {
        if ($mode !== 'subscribe') return false;

        $expected = $this->integration->getCredential('verify_token');
        if (empty($expected) || $token !== $expected) return false;

        return $challenge;
    }

    // ── Webhook Signature Check (POST) ────────────────────────────

    public function verifySignature(string $rawBody, string $signature): bool
    {
        $appSecret = $this->integration->getCredential('app_secret');
        if (empty($appSecret)) return true; // skip if not configured

        $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $appSecret);
        return hash_equals($expected, $signature);
    }

    // ── Process Incoming Webhook Payload ─────────────────────────

    public function processWebhook(array $payload): int
    {
        $imported = 0;

        if (($payload['object'] ?? '') !== 'page') return 0;

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                if (($change['field'] ?? '') !== 'leadgen') continue;

                $value    = $change['value'] ?? [];
                $leadgenId = $value['leadgen_id'] ?? null;
                $formId    = $value['form_id']    ?? null;

                if (!$leadgenId) continue;

                // Check if this form is in the allowed list (if tenant restricted)
                $allowedForms = $this->integration->settings['form_ids'] ?? [];
                if (!empty($allowedForms) && !in_array($formId, $allowedForms)) continue;

                $leadData = $this->fetchLeadData($leadgenId);
                if (!$leadData) continue;

                $this->createLead($leadData, $formId);
                $imported++;
            }
        }

        if ($imported > 0) {
            $this->integration->increment('leads_imported', $imported);
            $this->integration->update(['last_synced_at' => now()]);
        }

        return $imported;
    }

    // ── Fetch Lead Data from Graph API ────────────────────────────

    private function fetchLeadData(string $leadgenId): ?array
    {
        $accessToken = $this->integration->getCredential('page_access_token');
        if (empty($accessToken)) {
            Log::warning("Meta Lead Ads: no page_access_token for tenant {$this->integration->tenant_id}");
            return null;
        }

        $response = Http::get(self::GRAPH_URL . "/{$leadgenId}", [
            'access_token' => $accessToken,
            'fields'       => 'id,created_time,field_data,ad_id,ad_name,form_id',
        ]);

        if ($response->failed()) {
            Log::error("Meta Lead Ads: failed to fetch lead {$leadgenId}", [
                'tenant_id' => $this->integration->tenant_id,
                'error'     => $response->json(),
            ]);
            return null;
        }

        return $response->json();
    }

    // ── Create Lead in CRM ────────────────────────────────────────

    private function createLead(array $data, ?string $formId): void
    {
        $fields = [];
        foreach ($data['field_data'] ?? [] as $field) {
            $fields[$field['name']] = $field['values'][0] ?? null;
        }

        $name  = $fields['full_name']    ?? $fields['name']  ?? ($fields['first_name'] ?? '') . ' ' . ($fields['last_name'] ?? '');
        $phone = $fields['phone_number'] ?? $fields['phone'] ?? null;
        $email = $fields['email']        ?? null;
        $city  = $fields['city']         ?? null;

        // Skip if duplicate (same phone or email in same tenant in last 24h)
        if ($phone || $email) {
            $duplicate = Lead::where('tenant_id', $this->integration->tenant_id)
                ->where(function ($q) use ($phone, $email) {
                    if ($phone) $q->orWhere('phone', $phone);
                    if ($email) $q->orWhere('email', $email);
                })
                ->where('created_at', '>=', now()->subDay())
                ->exists();

            if ($duplicate) return;
        }

        // Map ad_name as notes
        $notes = $data['ad_name'] ? "Ad: {$data['ad_name']}" : null;
        if ($formId) $notes .= ($notes ? " | " : "") . "Form ID: {$formId}";

        Lead::create([
            'tenant_id'  => $this->integration->tenant_id,
            'name'       => trim($name) ?: 'Meta Lead',
            'phone'      => $phone,
            'email'      => $email,
            'city'       => $city,
            'source'     => 'facebook',
            'status'     => 'new',
            'priority'   => 'medium',
            'notes'      => $notes,
            'created_by' => null,
        ]);
    }
}
