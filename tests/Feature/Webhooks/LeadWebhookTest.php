<?php

namespace Tests\Feature\Webhooks;

use App\Models\Lead;
use App\Models\Tenant;
use App\Models\TenantIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LeadWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function integration(Tenant $tenant, string $platform, array $credentials = []): TenantIntegration
    {
        return TenantIntegration::create([
            'tenant_id'     => $tenant->id,
            'platform'      => $platform,
            'is_active'     => true,
            'credentials'   => $credentials,
            'webhook_token' => \Illuminate\Support\Str::random(40),
        ]);
    }

    public function test_invalid_webhook_token_returns_404(): void
    {
        $this->postJson('/webhook/leads/does-not-exist', ['name' => 'X'])->assertNotFound();
    }

    public function test_inactive_integration_returns_404(): void
    {
        $tenant = Tenant::factory()->create();
        $integration = $this->integration($tenant, 'tradeindia');
        $integration->update(['is_active' => false]);

        $this->postJson("/webhook/leads/{$integration->webhook_token}", ['name' => 'X'])->assertNotFound();
    }

    // ── Meta Lead Ads ────────────────────────────────────────────

    public function test_meta_webhook_rejects_invalid_signature(): void
    {
        $tenant = Tenant::factory()->create();
        $integration = $this->integration($tenant, 'meta_lead_ads', ['app_secret' => 'shh']);

        $this->postJson("/webhook/leads/{$integration->webhook_token}", ['object' => 'page', 'entry' => []], [
            'X-Hub-Signature-256' => 'sha256=invalid',
        ])->assertStatus(401);
    }

    public function test_meta_webhook_accepts_valid_signature(): void
    {
        $tenant = Tenant::factory()->create();
        $integration = $this->integration($tenant, 'meta_lead_ads', ['app_secret' => 'shh']);

        $payload = json_encode(['object' => 'page', 'entry' => []]);
        $signature = 'sha256=' . hash_hmac('sha256', $payload, 'shh');

        $this->call('POST', "/webhook/leads/{$integration->webhook_token}", [], [], [], [
            'HTTP_X-Hub-Signature-256' => $signature,
            'CONTENT_TYPE'             => 'application/json',
        ], $payload)->assertOk();
    }

    // ── JustDial ─────────────────────────────────────────────────

    public function test_justdial_webhook_rejects_wrong_key(): void
    {
        $tenant = Tenant::factory()->create();
        $integration = $this->integration($tenant, 'justdial', ['secret_key' => 'correct-key']);

        $this->postJson("/webhook/leads/{$integration->webhook_token}?key=wrong-key", [
            'name' => 'JD Lead', 'mobile' => '9555555555',
        ])->assertStatus(401);
    }

    public function test_justdial_webhook_creates_lead_with_correct_key(): void
    {
        $tenant = Tenant::factory()->create();
        $integration = $this->integration($tenant, 'justdial', ['secret_key' => 'correct-key']);

        $this->postJson("/webhook/leads/{$integration->webhook_token}?key=correct-key", [
            'name' => 'JD Lead', 'mobile' => '9555555555',
        ])->assertOk()->assertJson(['ok' => true, 'created' => true]);

        $this->assertDatabaseHas('leads', ['tenant_id' => $tenant->id, 'source' => 'justdial', 'phone' => '9555555555']);
    }

    // ── TradeIndia / Sulekha (generic) ──────────────────────────

    public function test_generic_webhook_is_open_when_no_secret_configured(): void
    {
        $tenant = Tenant::factory()->create();
        $integration = $this->integration($tenant, 'tradeindia');

        $this->postJson("/webhook/leads/{$integration->webhook_token}", [
            'name' => 'TI Lead', 'phone' => '9666666666',
        ])->assertOk()->assertJson(['ok' => true, 'created' => true]);

        $this->assertDatabaseHas('leads', ['tenant_id' => $tenant->id, 'source' => 'tradeindia', 'phone' => '9666666666']);
    }

    public function test_generic_webhook_rejects_wrong_secret(): void
    {
        $tenant = Tenant::factory()->create();
        $integration = $this->integration($tenant, 'sulekha', ['secret_key' => 'right-secret']);

        $this->postJson("/webhook/leads/{$integration->webhook_token}", [
            'name' => 'Sulekha Lead', 'phone' => '9777777777',
        ], ['X-Webhook-Key' => 'wrong-secret'])->assertStatus(401);
    }

    public function test_generic_webhook_accepts_correct_secret(): void
    {
        $tenant = Tenant::factory()->create();
        $integration = $this->integration($tenant, 'sulekha', ['secret_key' => 'right-secret']);

        $this->postJson("/webhook/leads/{$integration->webhook_token}", [
            'name' => 'Sulekha Lead', 'phone' => '9777777777',
        ], ['X-Webhook-Key' => 'right-secret'])->assertOk()->assertJson(['ok' => true, 'created' => true]);
    }

    public function test_generic_webhook_skips_duplicate_lead(): void
    {
        $tenant = Tenant::factory()->create();
        $integration = $this->integration($tenant, 'tradeindia');

        Lead::factory()->create(['tenant_id' => $tenant->id, 'phone' => '9888888888']);

        $this->postJson("/webhook/leads/{$integration->webhook_token}", [
            'name' => 'Duplicate', 'phone' => '9888888888',
        ])->assertOk()->assertJson(['ok' => true, 'created' => false]);

        $this->assertSame(1, Lead::where('tenant_id', $tenant->id)->where('phone', '9888888888')->count());
    }

    // ── IndiaMART (polling) ──────────────────────────────────────

    public function test_indiamart_sync_dedups_by_unique_query_id(): void
    {
        $tenant = Tenant::factory()->create();
        $integration = $this->integration($tenant, 'indiamart', [
            'glusr_usr_given_code' => 'test-key',
            'mobile'                => '9000000000',
        ]);

        Lead::factory()->create(['tenant_id' => $tenant->id, 'notes' => 'IMID:12345']);

        Http::fake([
            'mapi.indiamart.com/*' => Http::response([
                'STATUS'        => 200,
                'TOTAL_RECORDS' => 1,
                'DATA'          => [[
                    'UNIQUE_QUERY_ID' => '12345',
                    'SENDER_NAME'     => 'Dup Lead',
                    'SENDER_MOBILE'   => '9999999999',
                ]],
            ]),
        ]);

        $imported = \App\Services\Integrations\IndiaMartLeadService::forIntegration($integration)->sync();

        $this->assertSame(0, $imported);
        $this->assertSame(1, Lead::where('tenant_id', $tenant->id)->count());
    }
}
