<?php

namespace Tests\Feature\Tenant;

use App\Models\Plan;
use App\Models\Product;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class ManufacturingModuleGatingTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function subscribeToPlan($tenant, array $features): void
    {
        $plan = Plan::create([
            'name' => 'Test Plan ' . uniqid(),
            'slug' => 'test-plan-' . uniqid(),
            'monthly_price' => 0,
            'yearly_price' => 0,
            'features' => $features,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'yearly',
            'started_at' => now(),
            'ends_at' => now()->addYear(),
        ]);
    }

    // ── No plan feature, no manual override — blocked ───────────────

    public function test_work_orders_are_blocked_when_manufacturing_module_is_disabled(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $this->subscribeToPlan($tenant, ['leads' => 10, 'users' => 2]);

        $this->assertFalse($tenant->hasModuleEnabled('manufacturing'));

        $this->actingAs($admin)->get(route('tenant.work-orders.index'))
            ->assertForbidden();
    }

    public function test_product_batches_page_is_blocked_when_manufacturing_module_is_disabled(): void
    {
        $tenant  = $this->setUpTenant();
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $product = Product::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Steel Rod',
            'type'      => 'raw_material',
            'rate'      => 50,
            'tax_percent' => 18,
        ]);

        $this->actingAs($admin)->get(route('tenant.products.batches', $product->id))
            ->assertForbidden();
    }

    // ── Plan grants it — auto-unlocked, no superadmin action needed ──

    public function test_a_plan_that_includes_manufacturing_unlocks_it_automatically(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $this->subscribeToPlan($tenant, ['leads' => -1, 'users' => -1, 'manufacturing' => true]);

        $this->assertTrue($tenant->hasModuleEnabled('manufacturing'));
        $this->assertTrue($tenant->moduleIncludedInPlan('manufacturing'));

        $this->actingAs($admin)->get(route('tenant.work-orders.index'))
            ->assertOk();
    }

    // ── Superadmin manual override — grants it even on a plan that doesn't include it ──

    public function test_superadmin_manual_override_unlocks_work_orders_even_on_a_plan_without_it(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $this->subscribeToPlan($tenant, ['leads' => 10, 'users' => 2]); // no manufacturing

        $this->assertFalse($tenant->moduleIncludedInPlan('manufacturing'));

        $settings = $tenant->settings ?? [];
        $settings['modules']['manufacturing'] = true;
        $tenant->update(['settings' => $settings]);

        $this->assertTrue($tenant->fresh()->hasModuleEnabled('manufacturing'));

        $this->actingAs($admin)->get(route('tenant.work-orders.index'))
            ->assertOk();
    }

    public function test_superadmin_can_toggle_manufacturing_for_a_tenant(): void
    {
        $tenant     = $this->setUpTenant();
        $superadmin = $this->makeUser($tenant, 'superadmin');
        $this->subscribeToPlan($tenant, ['leads' => 10, 'users' => 2]);

        $this->actingAs($superadmin)
            ->post(route('superadmin.tenants.toggle-manufacturing', $tenant), ['enabled' => true])
            ->assertRedirect();

        $this->assertTrue($tenant->fresh()->hasModuleEnabled('manufacturing'));

        $this->actingAs($superadmin)
            ->post(route('superadmin.tenants.toggle-manufacturing', $tenant), ['enabled' => false])
            ->assertRedirect();

        $this->assertFalse($tenant->fresh()->hasModuleEnabled('manufacturing'));
    }

    // ── Superadmin can force-disable even when the plan includes it ──

    public function test_superadmin_can_force_disable_manufacturing_even_when_plan_includes_it(): void
    {
        $tenant     = $this->setUpTenant();
        $admin      = $this->makeUser($tenant, 'tenant_admin');
        $superadmin = $this->makeUser($tenant, 'superadmin');
        $this->subscribeToPlan($tenant, ['leads' => -1, 'users' => -1, 'manufacturing' => true]);

        $this->assertTrue($tenant->hasModuleEnabled('manufacturing'));

        $this->actingAs($superadmin)
            ->post(route('superadmin.tenants.toggle-manufacturing', $tenant), ['enabled' => false]);

        $this->assertFalse($tenant->fresh()->hasModuleEnabled('manufacturing'));
        $this->actingAs($admin)->get(route('tenant.work-orders.index'))
            ->assertForbidden();
    }

    public function test_clearing_the_override_reverts_to_plan_default(): void
    {
        $tenant     = $this->setUpTenant();
        $admin      = $this->makeUser($tenant, 'tenant_admin');
        $superadmin = $this->makeUser($tenant, 'superadmin');
        $this->subscribeToPlan($tenant, ['leads' => -1, 'users' => -1, 'manufacturing' => true]);

        $this->actingAs($superadmin)
            ->post(route('superadmin.tenants.toggle-manufacturing', $tenant), ['enabled' => false]);
        $this->assertFalse($tenant->fresh()->hasModuleEnabled('manufacturing'));

        $this->actingAs($superadmin)
            ->post(route('superadmin.tenants.clear-manufacturing-override', $tenant));

        // Override cleared -> falls back to the plan, which includes it.
        $this->assertNull($tenant->fresh()->moduleOverride('manufacturing'));
        $this->assertTrue($tenant->fresh()->hasModuleEnabled('manufacturing'));
        $this->actingAs($admin)->get(route('tenant.work-orders.index'))
            ->assertOk();
    }

    // ── BOM / Low Stock never gated regardless of manufacturing state ──

    public function test_bom_and_low_stock_suggestions_stay_available_regardless_of_manufacturing_toggle(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $this->subscribeToPlan($tenant, ['leads' => 10, 'users' => 2]);

        $this->assertFalse($tenant->hasModuleEnabled('manufacturing'));

        $this->actingAs($admin)->get(route('tenant.products.low-stock'))
            ->assertOk();
        $this->actingAs($admin)->get(route('tenant.products.create'))
            ->assertOk();
    }
}
