<?php

namespace Tests\Feature\Console;

use App\Events\LeadSlaBreached;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class CheckLeadSlaBreachesTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    public function test_flags_an_overdue_uncontacted_new_lead(): void
    {
        $tenant = $this->setUpTenant();
        $staff  = $this->makeUser($tenant, 'staff');

        $lead = Lead::factory()->create([
            'tenant_id'   => $tenant->id,
            'assigned_to' => $staff->id,
            'status'      => 'new',
        ]);
        $lead->forceFill(['created_at' => now()->subHours(5)])->save();

        Event::fake([LeadSlaBreached::class]);

        $this->artisan('leads:check-sla')->assertSuccessful();

        Event::assertDispatched(LeadSlaBreached::class, fn ($event) => $event->lead->id === $lead->id);
        $this->assertNotNull($lead->fresh()->sla_notified_at);
    }

    public function test_does_not_flag_a_lead_within_the_sla_window(): void
    {
        $tenant = $this->setUpTenant();
        $staff  = $this->makeUser($tenant, 'staff');

        $lead = Lead::factory()->create([
            'tenant_id'   => $tenant->id,
            'assigned_to' => $staff->id,
            'status'      => 'new',
        ]);
        $lead->forceFill(['created_at' => now()->subHours(1)])->save();

        Event::fake([LeadSlaBreached::class]);
        $this->artisan('leads:check-sla');

        Event::assertNotDispatched(LeadSlaBreached::class);
    }

    public function test_does_not_flag_a_lead_that_has_already_been_contacted(): void
    {
        $tenant = $this->setUpTenant();
        $staff  = $this->makeUser($tenant, 'staff');

        $lead = Lead::factory()->create([
            'tenant_id'    => $tenant->id,
            'assigned_to'  => $staff->id,
            'status'       => 'new',
            'contacted_at' => now()->subHours(1),
        ]);
        $lead->forceFill(['created_at' => now()->subHours(5)])->save();

        Event::fake([LeadSlaBreached::class]);
        $this->artisan('leads:check-sla');

        Event::assertNotDispatched(LeadSlaBreached::class);
    }

    public function test_does_not_re_flag_a_lead_already_notified(): void
    {
        $tenant = $this->setUpTenant();
        $staff  = $this->makeUser($tenant, 'staff');

        $lead = Lead::factory()->create([
            'tenant_id'   => $tenant->id,
            'assigned_to' => $staff->id,
            'status'      => 'new',
        ]);
        $lead->forceFill(['created_at' => now()->subHours(5)])->save();

        $this->artisan('leads:check-sla');

        Event::fake([LeadSlaBreached::class]);
        $this->artisan('leads:check-sla');

        Event::assertNotDispatched(LeadSlaBreached::class);
    }

    public function test_does_not_flag_an_unassigned_lead(): void
    {
        $tenant = $this->setUpTenant();

        $lead = Lead::factory()->create([
            'tenant_id'   => $tenant->id,
            'assigned_to' => null,
            'status'      => 'new',
        ]);
        $lead->forceFill(['created_at' => now()->subHours(5)])->save();

        Event::fake([LeadSlaBreached::class]);
        $this->artisan('leads:check-sla');

        Event::assertNotDispatched(LeadSlaBreached::class);
    }
}
