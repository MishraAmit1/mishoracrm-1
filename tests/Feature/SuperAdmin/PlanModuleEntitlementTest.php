<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

// Covers Customer Loyalty (and every other premium module in config/modules.php)
// being a first-class Plan feature: the superadmin plan editor persists it, and
// buying a plan that includes it unlocks the module without any per-tenant toggle.
class PlanModuleEntitlementTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function planPayload(array $overrides = []): array
    {
        return array_merge([
            'name'          => 'Growth',
            'slug'          => 'growth',
            'monthly_price' => 1499,
            'yearly_price'  => 14999,
            'sort_order'    => 5,
            'is_active'     => 1,
        ], $overrides);
    }

    private function subscribe(Tenant $tenant, array $features): Plan
    {
        $plan = Plan::create([
            'name'          => 'Test ' . uniqid(),
            'slug'          => 'test-' . uniqid(),
            'monthly_price' => 0,
            'yearly_price'  => 0,
            'features'      => $features,
            'is_active'     => true,
            'sort_order'    => 0,
        ]);

        Subscription::create([
            'tenant_id'     => $tenant->id,
            'plan_id'       => $plan->id,
            'status'        => 'active',
            'billing_cycle' => 'yearly',
            'started_at'    => now(),
            'ends_at'       => now()->addYear(),
        ]);

        return $plan;
    }

    // ── The plan editor can sell Loyalty ───────────────────────────

    public function test_superadmin_can_store_a_plan_with_the_loyalty_feature(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'superadmin');

        $this->actingAs($admin)
            ->post(route('superadmin.plans.store'), $this->planPayload([
                'feat_loyalty'       => '1',
                'feat_manufacturing' => '1',
            ]))
            ->assertRedirect(route('superadmin.plans.index'));

        $plan = Plan::where('slug', 'growth')->firstOrFail();

        $this->assertTrue($plan->features['loyalty']);
        $this->assertTrue($plan->features['manufacturing']);
        $this->assertArrayNotHasKey('tickets', $plan->features, 'unchecked modules must not be written');
    }

    public function test_superadmin_can_toggle_loyalty_off_a_plan_via_update(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'superadmin');

        $plan = Plan::create([
            'name' => 'Growth', 'slug' => 'growth', 'monthly_price' => 1499,
            'yearly_price' => 14999, 'sort_order' => 5, 'is_active' => true,
            'features' => ['leads' => -1, 'users' => -1, 'loyalty' => true],
        ]);

        $this->actingAs($admin)
            ->put(route('superadmin.plans.update', $plan), $this->planPayload())
            ->assertRedirect(route('superadmin.plans.index'));

        $this->assertArrayNotHasKey('loyalty', $plan->fresh()->features);
    }

    // ── Buying the plan unlocks the module ────────────────────────

    public function test_a_plan_that_includes_loyalty_unlocks_it_automatically(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $this->subscribe($tenant, ['leads' => -1, 'users' => -1, 'loyalty' => true]);

        $this->assertTrue($tenant->hasModuleEnabled('loyalty'));
        $this->assertTrue($tenant->moduleIncludedInPlan('loyalty'));

        $this->actingAs($admin)->get(route('tenant.loyalty.index'))->assertOk();
    }

    public function test_loyalty_is_locked_on_a_plan_without_it(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $this->subscribe($tenant, ['leads' => 10, 'users' => 2]);

        $this->assertFalse($tenant->hasModuleEnabled('loyalty'));

        $this->actingAs($admin)->get(route('tenant.loyalty.index'))->assertForbidden();
    }

    // ── Superadmin override still wins over the plan ──────────────

    public function test_superadmin_can_force_disable_loyalty_even_when_the_plan_includes_it(): void
    {
        $tenant     = $this->setUpTenant();
        $admin      = $this->makeUser($tenant, 'tenant_admin');
        $superadmin = $this->makeUser($tenant, 'superadmin');
        $this->subscribe($tenant, ['leads' => -1, 'users' => -1, 'loyalty' => true]);

        $this->actingAs($superadmin)
            ->post(route('superadmin.tenants.toggle-module', [$tenant, 'loyalty']), ['enabled' => false])
            ->assertRedirect();

        $this->assertFalse($tenant->fresh()->hasModuleEnabled('loyalty'));
        $this->actingAs($admin)->get(route('tenant.loyalty.index'))->assertForbidden();

        $this->actingAs($superadmin)
            ->post(route('superadmin.tenants.clear-module-override', [$tenant, 'loyalty']))
            ->assertRedirect();

        $this->assertNull($tenant->fresh()->moduleOverride('loyalty'));
        $this->assertTrue($tenant->fresh()->hasModuleEnabled('loyalty'));
    }
}
