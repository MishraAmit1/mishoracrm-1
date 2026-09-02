<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

// Covers the "Custom (Enterprise / Contact Sales)" plan: no price, no self-serve
// checkout, excluded from registration, and surfaced as "Custom" on pricing.
class EnterprisePlanTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function freePlan(): Plan
    {
        return Plan::create([
            'name' => 'Free Trial', 'slug' => 'free', 'monthly_price' => 0, 'yearly_price' => 0,
            'features' => ['users' => 2], 'is_active' => true, 'is_custom' => false, 'sort_order' => 0,
        ]);
    }

    private function customPlan(): Plan
    {
        return Plan::create([
            'name' => 'Enterprise', 'slug' => 'enterprise', 'monthly_price' => 0, 'yearly_price' => 0,
            'features' => ['users' => -1], 'is_active' => true, 'is_custom' => true, 'sort_order' => 9,
        ]);
    }

    public function test_storing_a_custom_plan_forces_price_to_zero(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'superadmin');

        $this->actingAs($admin)->post(route('superadmin.plans.store'), [
            'name' => 'Enterprise', 'slug' => 'enterprise', 'sort_order' => 9,
            'is_active' => 1, 'is_custom' => 1,
            'monthly_price' => 5000, 'yearly_price' => 50000,
            'users_count' => 25,
        ])->assertRedirect(route('superadmin.plans.index'));

        $plan = Plan::where('slug', 'enterprise')->firstOrFail();
        $this->assertTrue($plan->is_custom);
        $this->assertSame(0.0, (float) $plan->monthly_price);
        $this->assertSame(0.0, (float) $plan->yearly_price);
        $this->assertArrayNotHasKey('leads', $plan->features);
    }

    public function test_pricing_page_shows_custom_and_a_talk_to_sales_link(): void
    {
        $this->freePlan();
        $this->customPlan();

        $this->get(route('pricing'))
            ->assertOk()
            ->assertSee('Custom')
            ->assertSee(route('contact-sales'), false)
            ->assertDontSee('Unlimited leads', false); // dropped from the plan cards
    }

    public function test_register_page_excludes_custom_plans(): void
    {
        $this->freePlan();
        $this->customPlan();

        $this->get(route('register'))
            ->assertOk()
            ->assertDontSee('value="enterprise"', false);
    }

    public function test_checkout_404s_for_a_custom_plan(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $free   = $this->freePlan();
        $plan   = $this->customPlan();

        Subscription::create([
            'tenant_id' => $tenant->id, 'plan_id' => $free->id,
            'status' => 'active', 'billing_cycle' => 'monthly',
            'started_at' => now(), 'ends_at' => now()->addMonth(),
        ]);

        $this->actingAs($admin)
            ->get(route('tenant.subscription.checkout', [$plan->slug, 'monthly']))
            ->assertNotFound();
    }
}
