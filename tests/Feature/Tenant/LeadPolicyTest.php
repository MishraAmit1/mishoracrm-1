<?php

namespace Tests\Feature\Tenant;

use App\Models\Lead;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class LeadPolicyTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function roleWith(Tenant $tenant, string $roleName, array $permissions): void
    {
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        $role->syncPermissions($permissions);
    }

    public function test_view_own_user_can_see_only_assigned_leads(): void
    {
        $tenant = $this->setUpTenant();
        $this->roleWith($tenant, 'view_own_only', ['leads.view_own']);
        $user      = $this->makeUser($tenant, 'view_own_only');
        $otherUser = $this->makeUser($tenant, 'view_own_only');

        $myLead    = Lead::factory()->create(['tenant_id' => $tenant->id, 'assigned_to' => $user->id]);
        $otherLead = Lead::factory()->create(['tenant_id' => $tenant->id, 'assigned_to' => $otherUser->id]);

        $this->actingAs($user)->get(route('tenant.leads.show', $myLead->id))->assertOk();
        $this->actingAs($user)->get(route('tenant.leads.show', $otherLead->id))->assertForbidden();
    }

    public function test_view_all_user_can_see_any_tenant_lead(): void
    {
        $tenant = $this->setUpTenant();
        $this->roleWith($tenant, 'view_all_only', ['leads.view_all']);
        $user = $this->makeUser($tenant, 'view_all_only');

        $lead = Lead::factory()->create(['tenant_id' => $tenant->id, 'assigned_to' => null]);

        $this->actingAs($user)->get(route('tenant.leads.show', $lead->id))->assertOk();
    }

    public function test_user_with_neither_view_permission_sees_empty_index(): void
    {
        $tenant = $this->setUpTenant();
        $this->roleWith($tenant, 'no_view', []);
        $user = $this->makeUser($tenant, 'no_view');

        Lead::factory()->count(3)->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($user)->get(route('tenant.leads.index'));

        $response->assertOk();
        $this->assertSame(0, $response->viewData('leads')->total());
    }

    public function test_edit_own_user_cannot_update_a_lead_assigned_to_someone_else(): void
    {
        $tenant = $this->setUpTenant();
        $this->roleWith($tenant, 'edit_own_only', ['leads.view_own', 'leads.edit_own']);
        $user  = $this->makeUser($tenant, 'edit_own_only');
        $other = $this->makeUser($tenant, 'edit_own_only');

        $lead = Lead::factory()->create(['tenant_id' => $tenant->id, 'assigned_to' => $other->id]);

        $this->actingAs($user)->put(route('tenant.leads.update', $lead->id), [
            'name'  => 'Hijacked',
            'phone' => $lead->phone,
        ])->assertForbidden();
    }

    public function test_edit_all_user_can_update_any_lead(): void
    {
        $tenant = $this->setUpTenant();
        $this->roleWith($tenant, 'edit_all_only', ['leads.view_all', 'leads.edit_all']);
        $user = $this->makeUser($tenant, 'edit_all_only');

        $lead = Lead::factory()->create(['tenant_id' => $tenant->id, 'assigned_to' => null]);

        $this->actingAs($user)->put(route('tenant.leads.update', $lead->id), [
            'name'   => 'Updated By Admin',
            'phone'  => $lead->phone,
            'status' => $lead->status,
        ])->assertRedirect(route('tenant.leads.show', $lead->id));

        $this->assertSame('Updated By Admin', $lead->fresh()->name);
    }

    public function test_store_requires_leads_create_permission(): void
    {
        $tenant = $this->setUpTenant();
        $this->roleWith($tenant, 'no_create', ['leads.view_all']);
        $user = $this->makeUser($tenant, 'no_create');

        $this->actingAs($user)->post(route('tenant.leads.store'), [
            'name'  => 'Blocked Lead',
            'phone' => '9000000000',
        ])->assertForbidden();

        $this->assertDatabaseMissing('leads', ['phone' => '9000000000']);
    }

    public function test_assign_requires_leads_assign_permission(): void
    {
        $tenant = $this->setUpTenant();
        $this->roleWith($tenant, 'no_assign', ['leads.view_all', 'leads.edit_all']);
        $user   = $this->makeUser($tenant, 'no_assign');
        $target = $this->makeUser($tenant, 'no_assign');
        $lead   = Lead::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)->post(route('tenant.leads.assign', $lead->id), [
            'assigned_to' => $target->id,
        ])->assertForbidden();
    }

    public function test_convert_requires_leads_convert_permission_and_ownership(): void
    {
        $tenant = $this->setUpTenant();
        $this->roleWith($tenant, 'view_only_no_convert', ['leads.view_all', 'leads.edit_all']);
        $user = $this->makeUser($tenant, 'view_only_no_convert');
        $lead = Lead::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)->post(route('tenant.leads.convert', $lead->id))
            ->assertForbidden();
    }

    public function test_a_lead_from_another_tenant_returns_404_not_403(): void
    {
        $tenantA = $this->setUpTenant();
        $tenantB = Tenant::factory()->create();
        $admin   = $this->makeUser($tenantA, 'tenant_admin');
        $leadB   = Lead::factory()->create(['tenant_id' => $tenantB->id]);

        $this->actingAs($admin)->put(route('tenant.leads.update', $leadB->id), [
            'name' => 'x', 'phone' => 'x',
        ])->assertNotFound();
    }
}
