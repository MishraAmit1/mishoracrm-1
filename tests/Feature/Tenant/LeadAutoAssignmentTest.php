<?php

namespace Tests\Feature\Tenant;

use App\Models\Lead;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class LeadAutoAssignmentTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    public function test_unassigned_lead_gets_auto_assigned_to_active_staff(): void
    {
        $tenant = $this->setUpTenant();
        $staff  = $this->makeUser($tenant, 'staff');

        $lead = Lead::factory()->create(['tenant_id' => $tenant->id, 'assigned_to' => null]);

        $this->assertSame($staff->id, $lead->fresh()->assigned_to);
    }

    public function test_lead_goes_to_whoever_currently_has_fewest_open_leads(): void
    {
        $tenant  = $this->setUpTenant();
        $busy    = $this->makeUser($tenant, 'staff');
        $idle    = $this->makeUser($tenant, 'staff');

        Lead::factory()->count(3)->create(['tenant_id' => $tenant->id, 'assigned_to' => $busy->id, 'status' => 'new']);

        $newLead = Lead::factory()->create(['tenant_id' => $tenant->id, 'assigned_to' => null]);

        $this->assertSame($idle->id, $newLead->fresh()->assigned_to);
    }

    public function test_converted_and_lost_leads_do_not_count_toward_workload(): void
    {
        $tenant = $this->setUpTenant();
        $userA  = $this->makeUser($tenant, 'staff');
        $userB  = $this->makeUser($tenant, 'staff');

        // userA has more total leads, but they're all closed — should still be eligible.
        Lead::factory()->count(5)->create(['tenant_id' => $tenant->id, 'assigned_to' => $userA->id, 'status' => 'converted']);
        Lead::factory()->count(1)->create(['tenant_id' => $tenant->id, 'assigned_to' => $userB->id, 'status' => 'new']);

        $newLead = Lead::factory()->create(['tenant_id' => $tenant->id, 'assigned_to' => null]);

        $this->assertSame($userA->id, $newLead->fresh()->assigned_to);
    }

    public function test_an_already_assigned_lead_is_left_alone(): void
    {
        $tenant = $this->setUpTenant();
        $staff  = $this->makeUser($tenant, 'staff');
        $picked = $this->makeUser($tenant, 'staff');

        $lead = Lead::factory()->create(['tenant_id' => $tenant->id, 'assigned_to' => $picked->id]);

        $this->assertSame($picked->id, $lead->fresh()->assigned_to);
    }

    public function test_auto_assignment_only_considers_staff_from_the_same_tenant(): void
    {
        $tenantA = $this->setUpTenant();
        $tenantB = Tenant::factory()->create();
        $staffB  = $this->makeUser($tenantB, 'staff');

        $lead = Lead::factory()->create(['tenant_id' => $tenantA->id, 'assigned_to' => null]);

        $this->assertNotSame($staffB->id, $lead->fresh()->assigned_to);
        $this->assertNull($lead->fresh()->assigned_to);
    }

    public function test_inactive_staff_are_not_eligible(): void
    {
        $tenant = $this->setUpTenant();
        $inactive = $this->makeUser($tenant, 'staff');
        $inactive->update(['is_active' => false]);

        $lead = Lead::factory()->create(['tenant_id' => $tenant->id, 'assigned_to' => null]);

        $this->assertNull($lead->fresh()->assigned_to);
    }
}
