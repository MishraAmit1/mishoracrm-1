<?php

namespace Tests\Feature\Auth;

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

// Per-plan free trial (`plans.trial_days`) and how it drives the subscription
// window created at signup.
class RegistrationTrialTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    private function plan(array $overrides = []): Plan
    {
        return Plan::create(array_merge([
            'name' => 'P', 'slug' => 'p-' . uniqid(), 'monthly_price' => 0, 'yearly_price' => 0,
            'features' => ['users' => 3], 'is_active' => true, 'is_custom' => false,
            'trial_days' => 0, 'sort_order' => 1,
        ], $overrides));
    }

    private function register(string $planSlug): \Illuminate\Testing\TestResponse
    {
        return $this->post(route('register.store'), [
            'company_name' => 'Acme', 'subdomain' => 'acme' . random_int(100, 999),
            'first_name' => 'A', 'last_name' => 'B',
            'email' => 'a' . random_int(1000, 9999) . '@acme.test',
            'password' => 'Password1', 'password_confirmation' => 'Password1',
            'plan' => $planSlug, 'terms' => 'on',
        ]);
    }

    // ── Plan editor ───────────────────────────────────────────────

    public function test_plan_editor_persists_trial_days(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'superadmin');

        $this->actingAs($admin)->post(route('superadmin.plans.store'), [
            'name' => 'Starter', 'slug' => 'starter', 'sort_order' => 1,
            'is_active' => 1, 'monthly_price' => 999, 'yearly_price' => 9999,
            'trial_days' => 21, 'users_count' => 5,
        ])->assertRedirect();

        $this->assertSame(21, Plan::where('slug', 'starter')->value('trial_days'));
    }

    public function test_custom_plan_cannot_have_a_trial(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'superadmin');

        $this->actingAs($admin)->post(route('superadmin.plans.store'), [
            'name' => 'Ent', 'slug' => 'ent', 'sort_order' => 9,
            'is_active' => 1, 'is_custom' => 1, 'trial_days' => 30, 'users_count' => 10,
        ])->assertRedirect();

        $this->assertSame(0, Plan::where('slug', 'ent')->value('trial_days'));
    }

    // ── Signup → subscription window ──────────────────────────────

    public function test_signup_on_a_trial_plan_starts_an_n_day_trial(): void
    {
        $plan = $this->plan(['monthly_price' => 999, 'trial_days' => 10]);

        $this->register($plan->slug)->assertRedirect();

        $sub = Subscription::firstOrFail();
        $this->assertSame($plan->id, $sub->plan_id);
        $this->assertSame('trial', $sub->status);
        $this->assertEqualsWithDelta(now()->addDays(10)->timestamp, $sub->trial_ends_at->timestamp, 5);
    }

    public function test_signup_on_the_free_plan_with_no_trial_is_permanent(): void
    {
        $plan = $this->plan(['monthly_price' => 0, 'trial_days' => 0]);

        $this->register($plan->slug)->assertRedirect();

        $sub = Subscription::firstOrFail();
        $this->assertSame($plan->id, $sub->plan_id);
        $this->assertSame('active', $sub->status);
        $this->assertNull($sub->trial_ends_at);
        $this->assertNull($sub->ends_at);
    }

    public function test_signup_on_a_paid_plan_with_no_trial_redirects_to_checkout(): void
    {
        $plan = $this->plan(['monthly_price' => 1999, 'trial_days' => 0]);

        $this->register($plan->slug)
            ->assertRedirect(route('tenant.subscription.checkout', [$plan->slug, 'monthly']));

        $sub = Subscription::firstOrFail();
        $this->assertSame('trial', $sub->status);
        $this->assertTrue($sub->trial_ends_at->lessThanOrEqualTo(now()->addMinute()));
    }

    // ── Pricing page wording ─────────────────────────────────────

    public function test_pricing_cta_wording_matches_the_trial_config(): void
    {
        $this->plan(['name' => 'Free', 'slug' => 'free', 'monthly_price' => 0, 'trial_days' => 0, 'sort_order' => 0]);
        $this->plan(['name' => 'Starter', 'slug' => 'starter', 'monthly_price' => 999, 'trial_days' => 14, 'sort_order' => 1]);
        $this->plan(['name' => 'Pro', 'slug' => 'pro', 'monthly_price' => 2499, 'trial_days' => 0, 'sort_order' => 2]);

        $this->get(route('pricing'))
            ->assertOk()
            ->assertSee('Start free')            // free plan
            ->assertSee('Start 14-day trial')    // starter
            ->assertSee('Get started');          // pro (paid, no trial)
    }
}
