<?php

namespace Tests\Feature\Tenant;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

// The host never identifies the tenant on the shared domain, so the gate must
// resolve it from the logged-in user.
class SubscriptionGateTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    public function test_expired_tenant_is_redirected_to_expired_page(): void
    {
        $tenant = $this->setUpTenant();
        $tenant->subscriptions()->update(['status' => 'expired', 'ends_at' => now()->subDay()]);
        $user = $this->makeUser($tenant, 'tenant_admin');

        $this->actingAs($user)->get(route('tenant.leads.index'))
            ->assertRedirect(route('tenant.subscription.expired'));
    }

    public function test_expired_tenant_can_still_open_plans_page(): void
    {
        $tenant = $this->setUpTenant();
        $tenant->subscriptions()->update(['status' => 'expired', 'ends_at' => now()->subDay()]);
        $user = $this->makeUser($tenant, 'tenant_admin');

        $this->actingAs($user)->get(route('tenant.subscription.plans'))->assertOk();
    }

    public function test_tenant_without_any_subscription_is_locked(): void
    {
        $tenant = Tenant::factory()->create();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $user = $this->makeUser($tenant, 'tenant_admin');

        $this->actingAs($user)->get(route('tenant.leads.index'))
            ->assertRedirect(route('tenant.subscription.expired'));
    }

    public function test_active_tenant_is_not_redirected(): void
    {
        $tenant = $this->setUpTenant();
        $user = $this->makeUser($tenant, 'tenant_admin');

        $this->actingAs($user)->get(route('tenant.leads.index'))
            ->assertOk();
    }
}
