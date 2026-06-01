<?php

namespace App\Services\Integrations;

use App\Models\Lead;
use App\Models\TenantIntegration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * IndiaMART Lead Manager API Integration
 *
 * Type: POLLING (call on schedule, e.g. every 30 minutes)
 *
 * API Docs: https://seller.indiamart.com/crm/lead-manager
 *
 * Required credentials:
 *   - glusr_usr_given_code : IndiaMART CRM API key (from Seller Dashboard → CRM → Lead Manager API)
 *   - mobile               : Registered mobile number on IndiaMART account
 *
 * API Endpoint:
 *   GET https://mapi.indiamart.com/wservce/crm/crmListing/v2/
 *       ?glusr_usr_given_code={KEY}&start_time={START}&end_time={END}&start={OFFSET}&end={COUNT}
 */
class IndiaMartLeadService
{
    private const API_URL  = 'https://mapi.indiamart.com/wservce/crm/crmListing/v2/';
    private const PAGE_SIZE = 50;

    public function __construct(private TenantIntegration $integration) {}

    public static function forIntegration(TenantIntegration $integration): self
    {
        return new self($integration);
    }

    // ── Sync Leads (called by scheduler / Job) ────────────────────

    public function sync(): int
    {
        $apiKey = $this->integration->getCredential('glusr_usr_given_code');
        $mobile = $this->integration->getCredential('mobile');

        if (empty($apiKey) || empty($mobile)) {
            Log::warning("IndiaMART: missing credentials for tenant {$this->integration->tenant_id}");
            return 0;
        }

        $endTime   = now();
        $startTime = $this->integration->last_synced_at ?? now()->subDays(7);

        $imported = 0;
        $offset   = 1;

        do {
            $response = Http::get(self::API_URL, [
                'glusr_usr_given_code' => $apiKey,
                'start_time'           => $startTime->format('d-m-Y H:i:s'),
                'end_time'             => $endTime->format('d-m-Y H:i:s'),
                'start'                => $offset,
                'end'                  => self::PAGE_SIZE,
            ]);

            if ($response->failed()) {
                Log::error("IndiaMART: API error for tenant {$this->integration->tenant_id}", [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                break;
            }

            $data = $response->json();

            if (($data['STATUS'] ?? 0) != 200) {
                Log::warning("IndiaMART: non-200 status for tenant {$this->integration->tenant_id}", $data);
                break;
            }

            $records = $data['DATA'] ?? [];
            if (empty($records)) break;

            foreach ($records as $record) {
                if ($this->createLead($record)) $imported++;
            }

            $totalRecords = (int) ($data['TOTAL_RECORDS'] ?? 0);
            $offset += self::PAGE_SIZE;

        } while ($offset <= $totalRecords);

        if ($imported > 0) {
            $this->integration->increment('leads_imported', $imported);
        }

        $this->integration->update(['last_synced_at' => $endTime]);

        return $imported;
    }

    // ── Create Lead from IndiaMART Record ─────────────────────────

    private function createLead(array $record): bool
    {
        $phone = $record['SENDER_MOBILE'] ?? $record['SENDER_PHONE'] ?? null;
        $email = $record['SENDER_EMAIL']  ?? null;
        $name  = $record['SENDER_NAME']   ?? 'IndiaMART Lead';

        // Dedup by unique_query_id stored in notes, or phone
        $queryId = $record['UNIQUE_QUERY_ID'] ?? null;

        if ($queryId) {
            $exists = Lead::where('tenant_id', $this->integration->tenant_id)
                ->where('notes', 'like', "%IMID:{$queryId}%")
                ->exists();
            if ($exists) return false;
        } elseif ($phone) {
            $exists = Lead::where('tenant_id', $this->integration->tenant_id)
                ->where('phone', $phone)
                ->where('source', 'indiamart')
                ->where('created_at', '>=', now()->subDay())
                ->exists();
            if ($exists) return false;
        }

        $product = $record['PRODUCT_NAME'] ?? $record['SUBJECT'] ?? null;
        $message = $record['QUERY_MESSAGE'] ?? null;

        $notes = trim(implode("\n", array_filter([
            $queryId  ? "IMID:{$queryId}"          : null,
            $product  ? "Product: {$product}"      : null,
            $message  ? "Message: {$message}"      : null,
        ])));

        Lead::create([
            'tenant_id'  => $this->integration->tenant_id,
            'name'       => $name,
            'phone'      => $phone,
            'email'      => $email,
            'company'    => $record['SENDER_COMPANY'] ?? null,
            'city'       => $record['SENDER_CITY']    ?? $record['SENDER_ADDRESS'] ?? null,
            'source'     => 'indiamart',
            'status'     => 'new',
            'priority'   => 'medium',
            'notes'      => $notes ?: null,
            'created_by' => null,
        ]);

        return true;
    }

    // ── Test Credentials ──────────────────────────────────────────

    public function testCredentials(): bool
    {
        $apiKey = $this->integration->getCredential('glusr_usr_given_code');
        if (empty($apiKey)) return false;

        $response = Http::get(self::API_URL, [
            'glusr_usr_given_code' => $apiKey,
            'start_time'           => now()->subHour()->format('d-m-Y H:i:s'),
            'end_time'             => now()->format('d-m-Y H:i:s'),
            'start'                => 1,
            'end'                  => 1,
        ]);

        return $response->ok() && ($response->json('STATUS') == 200 || $response->json('STATUS') == 204);
    }
}
