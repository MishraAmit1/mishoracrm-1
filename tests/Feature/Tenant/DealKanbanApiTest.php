<?php

namespace Tests\Feature\Tenant;

use App\Models\ApiKey;
use App\Models\Deal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

// Covers the API DealController methods (kanban/stats/updateStage/markWon/
// markLost) that already existed in the controller but were never
// registered in routes/api.php before this Deal-module remediation pass.
class DealKanbanApiTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function apiKeyFor(\App\Models\Tenant $tenant, \App\Models\User $user): string
    {
        return ApiKey::generate($tenant->id, $user->id, 'test key')->key;
    }

    public function test_kanban_endpoint_returns_deals_grouped_by_stage(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $key    = $this->apiKeyFor($tenant, $admin);
        Deal::factory()->create(['tenant_id' => $tenant->id, 'stage' => 'new']);
        Deal::factory()->create(['tenant_id' => $tenant->id, 'stage' => 'won']);

        $this->withHeader('X-API-Key', $key)
            ->getJson('/api/v1/tenant/deals/kanban')
            ->assertOk()
            ->assertJsonStructure(['success', 'data' => ['deals', 'summary', 'stages']]);
    }

    public function test_stats_endpoint_returns_pipeline_totals(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $key    = $this->apiKeyFor($tenant, $admin);
        Deal::factory()->create(['tenant_id' => $tenant->id, 'stage' => 'new', 'value' => 1000]);

        $this->withHeader('X-API-Key', $key)
            ->getJson('/api/v1/tenant/deals/stats')
            ->assertOk()
            ->assertJsonStructure(['success', 'data' => ['total_deals', 'open_deals', 'pipeline_value', 'by_stage']]);
    }

    public function test_update_stage_endpoint_moves_a_deal(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $key    = $this->apiKeyFor($tenant, $admin);
        $deal   = Deal::factory()->create(['tenant_id' => $tenant->id, 'stage' => 'new']);

        $this->withHeader('X-API-Key', $key)
            ->patchJson('/api/v1/tenant/deals/' . $deal->id . '/stage', ['stage' => 'proposal'])
            ->assertOk();

        $this->assertSame('proposal', $deal->fresh()->stage);
    }

    public function test_mark_won_endpoint_marks_a_deal_won(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $key    = $this->apiKeyFor($tenant, $admin);
        $deal   = Deal::factory()->create(['tenant_id' => $tenant->id, 'stage' => 'negotiation']);

        $this->withHeader('X-API-Key', $key)
            ->postJson('/api/v1/tenant/deals/' . $deal->id . '/mark-won')
            ->assertOk();

        $this->assertSame('won', $deal->fresh()->stage);
    }

    public function test_mark_lost_endpoint_marks_a_deal_lost(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $key    = $this->apiKeyFor($tenant, $admin);
        $deal   = Deal::factory()->create(['tenant_id' => $tenant->id, 'stage' => 'proposal']);

        $this->withHeader('X-API-Key', $key)
            ->postJson('/api/v1/tenant/deals/' . $deal->id . '/mark-lost', ['lost_reason' => 'Too expensive'])
            ->assertOk();

        $this->assertSame('lost', $deal->fresh()->stage);
    }

    public function test_store_requires_deals_create_permission(): void
    {
        $tenant = $this->setUpTenant();
        $noPerm = $this->makeUser($tenant, 'staff');
        $key    = $this->apiKeyFor($tenant, $noPerm);

        $this->withHeader('X-API-Key', $key)
            ->postJson('/api/v1/tenant/deals', ['title' => 'Blocked', 'value' => 100, 'stage' => 'new'])
            ->assertForbidden();
    }
}
