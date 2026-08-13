<?php

namespace Tests\Feature\Tenant;

use App\Models\Lead;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class LeadCrudTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    public function test_tenant_admin_can_create_a_lead(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $response = $this->actingAs($admin)->post(route('tenant.leads.store'), [
            'name'   => 'Jane Doe',
            'phone'  => '9876543210',
            'email'  => 'jane@example.com',
            'source' => 'website',
            'status' => 'new',
        ]);

        $lead = Lead::where('phone', '9876543210')->first();

        $this->assertNotNull($lead);
        $response->assertRedirect(route('tenant.leads.show', $lead->id));
        $this->assertSame($tenant->id, $lead->tenant_id);
    }

    public function test_show_edit_update_happy_path(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $lead   = Lead::factory()->create(['tenant_id' => $tenant->id, 'assigned_to' => $admin->id]);

        $this->actingAs($admin)->get(route('tenant.leads.show', $lead->id))->assertOk();
        $this->actingAs($admin)->get(route('tenant.leads.edit', $lead->id))->assertOk();

        $response = $this->actingAs($admin)->put(route('tenant.leads.update', $lead->id), [
            'name'   => 'Updated Name',
            'phone'  => $lead->phone,
            'email'  => $lead->email,
            'status' => $lead->status,
        ]);

        $response->assertRedirect(route('tenant.leads.show', $lead->id));
        $this->assertSame('Updated Name', $lead->fresh()->name);
    }

    public function test_destroy_deletes_a_non_converted_lead(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $lead   = Lead::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($admin)->delete(route('tenant.leads.destroy', $lead->id))
            ->assertRedirect(route('tenant.leads.index'));

        $this->assertSoftDeleted($lead);
    }

    public function test_converted_lead_cannot_be_deleted(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $lead   = Lead::factory()->create(['tenant_id' => $tenant->id, 'status' => 'converted']);

        $this->actingAs($admin)->delete(route('tenant.leads.destroy', $lead->id))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted($lead);
    }

    public function test_a_lead_from_another_tenant_is_not_visible(): void
    {
        $tenantA = $this->setUpTenant();
        $tenantB = Tenant::factory()->create();
        $adminA  = $this->makeUser($tenantA, 'tenant_admin');
        $leadB   = Lead::factory()->create(['tenant_id' => $tenantB->id]);

        $this->actingAs($adminA)->get(route('tenant.leads.show', $leadB->id))
            ->assertNotFound();
    }

    public function test_kanban_shows_more_than_the_old_twenty_per_page_cap(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        // More than the list's 20/page, well under the kanban's 500 cap —
        // proves the Kanban board no longer silently truncates to one page.
        Lead::factory()->count(25)->create(['tenant_id' => $tenant->id, 'status' => 'new']);

        $response = $this->actingAs($admin)->get(route('tenant.leads.index'));

        $response->assertOk();
        $kanbanLeads = $response->viewData('kanbanLeads');
        $this->assertSame(25, $kanbanLeads->where('status', 'new')->count());
        $this->assertFalse($response->viewData('kanbanCapped'));
    }
}
