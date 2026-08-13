<?php

namespace Tests\Feature\Tenant;

use App\Models\Lead;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class LeadBulkActionsTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    public function test_bulk_status_update_requires_edit_permission(): void
    {
        $tenant = $this->setUpTenant();
        Role::firstOrCreate(['name' => 'no_edit', 'guard_name' => 'web'])->syncPermissions([]);
        $user  = $this->makeUser($tenant, 'no_edit');
        $leads = Lead::factory()->count(2)->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)->postJson(route('tenant.leads.bulk-status'), [
            'ids'    => $leads->pluck('id')->all(),
            'status' => 'contacted',
        ])->assertForbidden();
    }

    public function test_bulk_status_update_stamps_contacted_at(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $leads  = Lead::factory()->count(2)->create(['tenant_id' => $tenant->id, 'status' => 'new']);

        $this->actingAs($admin)->postJson(route('tenant.leads.bulk-status'), [
            'ids'    => $leads->pluck('id')->all(),
            'status' => 'contacted',
        ])->assertOk()->assertJson(['updated' => 2]);

        foreach ($leads as $lead) {
            $lead->refresh();
            $this->assertSame('contacted', $lead->status);
            $this->assertNotNull($lead->contacted_at);
        }
    }

    public function test_bulk_destroy_requires_delete_permission(): void
    {
        $tenant = $this->setUpTenant();
        Role::firstOrCreate(['name' => 'no_delete', 'guard_name' => 'web'])->syncPermissions(['leads.view_all']);
        $user = $this->makeUser($tenant, 'no_delete');
        $lead = Lead::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)->postJson(route('tenant.leads.bulk-destroy'), [
            'ids' => [$lead->id],
        ])->assertForbidden();
    }

    public function test_bulk_destroy_skips_converted_leads(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $normal = Lead::factory()->create(['tenant_id' => $tenant->id]);
        $converted = Lead::factory()->create(['tenant_id' => $tenant->id, 'status' => 'converted']);

        $response = $this->actingAs($admin)->postJson(route('tenant.leads.bulk-destroy'), [
            'ids' => [$normal->id, $converted->id],
        ]);

        $response->assertOk()->assertJson(['deleted' => 1, 'skipped_converted' => 1]);
        $this->assertSoftDeleted($normal);
        $this->assertNotSoftDeleted($converted);
    }

    public function test_bulk_assign_requires_assign_permission(): void
    {
        $tenant = $this->setUpTenant();
        Role::firstOrCreate(['name' => 'no_assign', 'guard_name' => 'web'])->syncPermissions(['leads.view_all', 'leads.edit_all']);
        $user   = $this->makeUser($tenant, 'no_assign');
        $target = $this->makeUser($tenant, 'no_assign');
        $lead   = Lead::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)->postJson(route('tenant.leads.bulk-assign'), [
            'ids'         => [$lead->id],
            'assigned_to' => $target->id,
        ])->assertForbidden();
    }

    public function test_bulk_actions_do_not_affect_leads_from_another_tenant(): void
    {
        $tenantA = $this->setUpTenant();
        $tenantB = Tenant::factory()->create();
        $admin   = $this->makeUser($tenantA, 'tenant_admin');
        $leadA   = Lead::factory()->create(['tenant_id' => $tenantA->id, 'status' => 'new']);
        $leadB   = Lead::factory()->create(['tenant_id' => $tenantB->id, 'status' => 'new']);

        $this->actingAs($admin)->postJson(route('tenant.leads.bulk-status'), [
            'ids'    => [$leadA->id, $leadB->id],
            'status' => 'qualified',
        ])->assertOk()->assertJson(['updated' => 1]);

        $this->assertSame('qualified', $leadA->fresh()->status);
        $this->assertSame('new', $leadB->fresh()->status);
    }
}
