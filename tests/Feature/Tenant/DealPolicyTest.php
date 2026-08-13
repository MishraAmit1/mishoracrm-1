<?php

namespace Tests\Feature\Tenant;

use App\Models\Deal;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class DealPolicyTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function roleWith(Tenant $tenant, string $roleName, array $permissions): void
    {
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        $role->syncPermissions($permissions);
    }

    public function test_view_own_user_can_see_only_assigned_deals(): void
    {
        $tenant = $this->setUpTenant();
        $this->roleWith($tenant, 'deals_view_own_only', ['deals.view_own']);
        $user      = $this->makeUser($tenant, 'deals_view_own_only');
        $otherUser = $this->makeUser($tenant, 'deals_view_own_only');

        $myDeal    = Deal::factory()->create(['tenant_id' => $tenant->id, 'assigned_to' => $user->id]);
        $otherDeal = Deal::factory()->create(['tenant_id' => $tenant->id, 'assigned_to' => $otherUser->id]);

        $this->actingAs($user)->get(route('tenant.deals.show', $myDeal->id))->assertOk();
        $this->actingAs($user)->get(route('tenant.deals.show', $otherDeal->id))->assertForbidden();
    }

    public function test_view_all_user_can_see_any_tenant_deal(): void
    {
        $tenant = $this->setUpTenant();
        $this->roleWith($tenant, 'deals_view_all_only', ['deals.view_all']);
        $user = $this->makeUser($tenant, 'deals_view_all_only');

        $deal = Deal::factory()->create(['tenant_id' => $tenant->id, 'assigned_to' => null]);

        $this->actingAs($user)->get(route('tenant.deals.show', $deal->id))->assertOk();
    }

    public function test_edit_own_user_cannot_update_a_deal_assigned_to_someone_else(): void
    {
        $tenant = $this->setUpTenant();
        $this->roleWith($tenant, 'deals_edit_own_only', ['deals.view_own', 'deals.edit_own']);
        $user  = $this->makeUser($tenant, 'deals_edit_own_only');
        $other = $this->makeUser($tenant, 'deals_edit_own_only');

        $deal = Deal::factory()->create(['tenant_id' => $tenant->id, 'assigned_to' => $other->id]);

        $this->actingAs($user)->put(route('tenant.deals.update', $deal->id), [
            'title' => 'Hijacked',
            'value' => $deal->value,
            'stage' => $deal->stage,
        ])->assertForbidden();
    }

    public function test_edit_all_user_can_update_any_deal(): void
    {
        $tenant = $this->setUpTenant();
        $this->roleWith($tenant, 'deals_edit_all_only', ['deals.view_all', 'deals.edit_all']);
        $user = $this->makeUser($tenant, 'deals_edit_all_only');

        $deal = Deal::factory()->create(['tenant_id' => $tenant->id, 'assigned_to' => null]);

        $this->actingAs($user)->put(route('tenant.deals.update', $deal->id), [
            'title' => 'Updated By Admin',
            'value' => $deal->value,
            'stage' => $deal->stage,
        ])->assertRedirect(route('tenant.deals.show', $deal->id));

        $this->assertSame('Updated By Admin', $deal->fresh()->title);
    }

    // Regression test for the Phase A fix: store() previously never called
    // authorize('create', Deal::class), so any authenticated user — even
    // one with zero deals permissions — could create deals.
    public function test_store_requires_deals_create_permission(): void
    {
        $tenant = $this->setUpTenant();
        $this->roleWith($tenant, 'deals_no_create', ['deals.view_all']);
        $user = $this->makeUser($tenant, 'deals_no_create');

        $this->actingAs($user)->post(route('tenant.deals.store'), [
            'title' => 'Blocked Deal',
            'value' => 1000,
            'stage' => 'new',
        ])->assertForbidden();

        $this->assertDatabaseMissing('deals', ['title' => 'Blocked Deal']);
    }

    // Regression test for the Phase A fix: destroy() previously reused the
    // modify() ability (edit_own/edit_all), so a user with deals.edit_all
    // but not the seeded deals.delete permission could still delete deals.
    public function test_destroy_requires_deals_delete_permission(): void
    {
        $tenant = $this->setUpTenant();
        $this->roleWith($tenant, 'deals_edit_all_no_delete', ['deals.view_all', 'deals.edit_all']);
        $user = $this->makeUser($tenant, 'deals_edit_all_no_delete');
        $deal = Deal::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)->delete(route('tenant.deals.destroy', $deal->id))
            ->assertForbidden();

        $this->assertNotSoftDeleted($deal);
    }

    public function test_destroy_allowed_with_deals_delete_permission(): void
    {
        $tenant = $this->setUpTenant();
        $this->roleWith($tenant, 'deals_can_delete', ['deals.view_all', 'deals.delete']);
        $user = $this->makeUser($tenant, 'deals_can_delete');
        $deal = Deal::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)->delete(route('tenant.deals.destroy', $deal->id))
            ->assertRedirect(route('tenant.deals.index'));

        $this->assertSoftDeleted($deal);
    }

    public function test_a_deal_from_another_tenant_returns_404_not_403(): void
    {
        $tenantA = $this->setUpTenant();
        $tenantB = Tenant::factory()->create();
        $admin   = $this->makeUser($tenantA, 'tenant_admin');
        $dealB   = Deal::factory()->create(['tenant_id' => $tenantB->id]);

        $this->actingAs($admin)->put(route('tenant.deals.update', $dealB->id), [
            'title' => 'x', 'value' => 1, 'stage' => 'new',
        ])->assertNotFound();
    }
}
