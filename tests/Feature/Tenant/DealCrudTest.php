<?php

namespace Tests\Feature\Tenant;

use App\Models\Deal;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class DealCrudTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    public function test_tenant_admin_can_create_a_deal(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $response = $this->actingAs($admin)->post(route('tenant.deals.store'), [
            'title' => 'New ERP Deal',
            'value' => 50000,
            'stage' => 'new',
        ]);

        $deal = Deal::where('title', 'New ERP Deal')->first();

        $this->assertNotNull($deal);
        $response->assertRedirect(route('tenant.deals.show', $deal->id));
        $this->assertSame($tenant->id, $deal->tenant_id);
        $this->assertSame(10, $deal->probability);
    }

    public function test_show_edit_update_happy_path(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $deal   = Deal::factory()->create(['tenant_id' => $tenant->id, 'assigned_to' => $admin->id]);

        $this->actingAs($admin)->get(route('tenant.deals.show', $deal->id))->assertOk();
        $this->actingAs($admin)->get(route('tenant.deals.edit', $deal->id))->assertOk();

        $response = $this->actingAs($admin)->put(route('tenant.deals.update', $deal->id), [
            'title' => 'Updated Title',
            'value' => $deal->value,
            'stage' => $deal->stage,
        ]);

        $response->assertRedirect(route('tenant.deals.show', $deal->id));
        $this->assertSame('Updated Title', $deal->fresh()->title);
    }

    public function test_destroy_deletes_a_deal(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $deal   = Deal::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($admin)->delete(route('tenant.deals.destroy', $deal->id))
            ->assertRedirect(route('tenant.deals.index'));

        $this->assertSoftDeleted($deal);
    }

    public function test_mark_won_sets_stage_probability_and_close_date(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $deal   = Deal::factory()->create(['tenant_id' => $tenant->id, 'stage' => 'negotiation']);

        $this->actingAs($admin)->post(route('tenant.deals.mark_won', $deal->id))
            ->assertRedirect();

        $deal->refresh();
        $this->assertSame('won', $deal->stage);
        $this->assertSame(100, $deal->probability);
        $this->assertNotNull($deal->actual_close_date);
    }

    public function test_mark_lost_sets_stage_probability_and_reason(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $deal   = Deal::factory()->create(['tenant_id' => $tenant->id, 'stage' => 'proposal']);

        $this->actingAs($admin)->post(route('tenant.deals.mark_lost', $deal->id), [
            'lost_reason' => 'Budget cut',
        ])->assertRedirect();

        $deal->refresh();
        $this->assertSame('lost', $deal->stage);
        $this->assertSame(0, $deal->probability);
        $this->assertSame('Budget cut', $deal->lost_reason);
    }

    public function test_a_deal_from_another_tenant_returns_404(): void
    {
        $tenantA = $this->setUpTenant();
        $tenantB = Tenant::factory()->create();
        $adminA  = $this->makeUser($tenantA, 'tenant_admin');
        $dealB   = Deal::factory()->create(['tenant_id' => $tenantB->id]);

        $this->actingAs($adminA)->get(route('tenant.deals.show', $dealB->id))
            ->assertNotFound();
    }

    public function test_export_requires_deals_export_permission(): void
    {
        $tenant = $this->setUpTenant();
        $staff  = $this->makeUser($tenant, 'staff');

        $this->actingAs($staff)->get(route('tenant.deals.export'))
            ->assertForbidden();
    }

    public function test_tenant_admin_can_export_deals(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        Deal::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($admin)->get(route('tenant.deals.export'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    // Regression test for the Phase E query rewrite: pipelineAnalytics()
    // used to loop 6 months with 3 queries each; this confirms the
    // single-query + PHP-grouping replacement still buckets a won deal
    // into the correct month's revenue and win count.
    public function test_pipeline_analytics_buckets_a_won_deal_into_the_correct_month(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin', ['deals.view_all']);
        Deal::factory()->create([
            'tenant_id'         => $tenant->id,
            'stage'             => 'won',
            'value'             => 25000,
            'actual_close_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($admin)->get(route('tenant.deals.pipeline'));

        $response->assertOk();
        $winLossData = $response->viewData('winLossData');
        $thisMonth   = $winLossData->firstWhere('month', now()->format('M'));
        $monthRevenue = $response->viewData('monthlyRevenue')->firstWhere('month', now()->format('M Y'));

        $this->assertSame(1, $thisMonth['won']);
        $this->assertEqualsWithDelta(25000.0, $monthRevenue['value'], 0.01);
    }
}
