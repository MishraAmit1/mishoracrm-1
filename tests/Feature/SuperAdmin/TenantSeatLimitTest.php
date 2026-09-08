<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

// Covers the superadmin per-tenant "User Seats" override — a tri-state on
// settings['limits']['users'] that supersedes the plan's features['users']
// (see Tenant::userSeatLimit() and StaffController::store()).
class TenantSeatLimitTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function subscribe(Tenant $tenant, int $planSeats): Plan
    {
        $plan = Plan::create([
            'name' => 'Seat Test', 'slug' => 'seat-test-' . uniqid(),
            'monthly_price' => 0, 'yearly_price' => 0,
            'features' => ['users' => $planSeats], 'is_active' => true, 'sort_order' => 0,
        ]);

        Subscription::create([
            'tenant_id' => $tenant->id, 'plan_id' => $plan->id,
            'status' => 'active', 'billing_cycle' => 'yearly',
            'started_at' => now(), 'ends_at' => now()->addYear(),
        ]);

        return $plan;
    }

    private function staffPayload(string $email): array
    {
        return [
            'name'     => 'New Person',
            'email'    => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role'     => 'staff',
        ];
    }

    // ── Model tri-state ───────────────────────────────────────────

    public function test_seat_limit_follows_the_plan_when_no_override_is_set(): void
    {
        $tenant = $this->setUpTenant();
        $this->subscribe($tenant, 3);
        $tenant = $tenant->fresh()->load('subscription.plan');

        $this->assertNull($tenant->userSeatLimitOverride());
        $this->assertSame(3, $tenant->userSeatLimit());
        $this->assertTrue($tenant->enforcesUserSeatLimit());
    }

    public function test_a_fixed_override_supersedes_the_plan(): void
    {
        $tenant = $this->setUpTenant();
        $this->subscribe($tenant, 3);

        $tenant->update(['settings' => ['limits' => ['users' => 10]]]);

        $this->assertSame(10, $tenant->fresh()->load('subscription.plan')->userSeatLimit());
    }

    public function test_an_unlimited_override_removes_the_cap(): void
    {
        $tenant = $this->setUpTenant();
        $this->subscribe($tenant, 3);

        $tenant->update(['settings' => ['limits' => ['users' => -1]]]);
        $tenant = $tenant->fresh()->load('subscription.plan');

        $this->assertSame(-1, $tenant->userSeatLimit());
        $this->assertFalse($tenant->enforcesUserSeatLimit());
    }

    // ── Superadmin controls ──────────────────────────────────────

    public function test_superadmin_can_set_a_fixed_seat_override(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'superadmin');
        $this->subscribe($tenant, 3);

        $this->actingAs($admin)
            ->post(route('superadmin.tenants.update-seat-limit', $tenant), ['mode' => 'fixed', 'seats' => 25])
            ->assertRedirect();

        $this->assertSame(25, $tenant->fresh()->load('subscription.plan')->userSeatLimit());
        $this->assertDatabaseHas('audit_logs', ['action' => 'seat_limit_set', 'model_id' => $tenant->id]);
    }

    public function test_superadmin_can_set_unlimited_seats(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'superadmin');
        $this->subscribe($tenant, 3);

        $this->actingAs($admin)
            ->post(route('superadmin.tenants.update-seat-limit', $tenant), ['mode' => 'unlimited'])
            ->assertRedirect();

        $this->assertSame(-1, $tenant->fresh()->userSeatLimitOverride());
    }

    public function test_fixed_mode_requires_a_seat_count(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'superadmin');

        $this->actingAs($admin)
            ->post(route('superadmin.tenants.update-seat-limit', $tenant), ['mode' => 'fixed'])
            ->assertSessionHasErrors('seats');
    }

    public function test_superadmin_can_reset_the_override_back_to_the_plan(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'superadmin');
        $this->subscribe($tenant, 3);
        $tenant->update(['settings' => ['limits' => ['users' => 99]]]);

        $this->actingAs($admin)
            ->post(route('superadmin.tenants.clear-seat-limit', $tenant))
            ->assertRedirect();

        $tenant = $tenant->fresh()->load('subscription.plan');
        $this->assertNull($tenant->userSeatLimitOverride());
        $this->assertSame(3, $tenant->userSeatLimit());
        $this->assertDatabaseHas('audit_logs', ['action' => 'seat_limit_reset', 'model_id' => $tenant->id]);
    }

    public function test_a_non_superadmin_cannot_change_the_seat_limit(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $this->actingAs($admin)
            ->post(route('superadmin.tenants.update-seat-limit', $tenant), ['mode' => 'fixed', 'seats' => 50])
            ->assertForbidden();
    }

    // ── Enforcement in StaffController::store() ───────────────────

    public function test_enforcement_uses_the_plan_limit_when_no_override_is_set(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin'); // 1 active user
        $this->subscribe($tenant, 1);                       // plan allows only 1

        $this->actingAs($admin)
            ->post(route('tenant.staffs.store'), $this->staffPayload('blocked@example.com'))
            ->assertSessionHas('error');

        $this->assertSame(1, User::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count());
    }

    public function test_a_superadmin_override_raises_the_cap_above_the_plan(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin'); // 1 active user
        $this->subscribe($tenant, 1);                       // plan alone would block a 2nd
        $tenant->update(['settings' => ['limits' => ['users' => 5]]]);

        $this->actingAs($admin)
            ->post(route('tenant.staffs.store'), $this->staffPayload('allowed@example.com'))
            ->assertSessionHas('success');

        $this->assertSame(2, User::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count());
    }

    public function test_a_superadmin_override_lowers_the_cap_below_the_plan(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $this->subscribe($tenant, 20);
        $tenant->update(['settings' => ['limits' => ['users' => 1]]]); // superadmin caps at 1

        $this->actingAs($admin)
            ->post(route('tenant.staffs.store'), $this->staffPayload('overcap@example.com'))
            ->assertSessionHas('error');

        $this->assertSame(1, User::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count());
    }
}
