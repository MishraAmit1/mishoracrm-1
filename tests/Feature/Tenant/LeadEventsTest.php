<?php

namespace Tests\Feature\Tenant;

use App\Events\LeadAssigned;
use App\Events\LeadConverted;
use App\Events\LeadCreated;
use App\Events\LeadStatusChanged;
use App\Models\Lead;
use App\Models\Notification;
use App\Models\TenantWebhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class LeadEventsTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    public function test_creating_a_lead_dispatches_lead_created(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        Event::fake([LeadCreated::class]);

        Lead::factory()->create(['tenant_id' => $tenant->id]);

        Event::assertDispatched(LeadCreated::class);
    }

    public function test_status_change_dispatches_lead_status_changed_with_old_and_new_values(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $lead   = Lead::factory()->create(['tenant_id' => $tenant->id, 'status' => 'new']);

        Event::fake([LeadStatusChanged::class]);
        $lead->update(['status' => 'contacted']);

        Event::assertDispatched(LeadStatusChanged::class, function ($event) use ($lead) {
            return $event->lead->id === $lead->id
                && $event->oldStatus === 'new'
                && $event->newStatus === 'contacted';
        });
    }

    public function test_assign_dispatches_lead_assigned(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $target = $this->makeUser($tenant, 'tenant_admin');
        $lead   = Lead::factory()->create(['tenant_id' => $tenant->id, 'assigned_to' => $admin->id]);

        Event::fake([LeadAssigned::class]);

        $this->actingAs($admin)->post(route('tenant.leads.assign', $lead->id), [
            'assigned_to' => $target->id,
        ]);

        Event::assertDispatched(LeadAssigned::class, fn ($event) => $event->lead->id === $lead->id);
    }

    public function test_convert_dispatches_lead_converted(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $lead   = Lead::factory()->create(['tenant_id' => $tenant->id]);

        Event::fake([LeadConverted::class]);

        $this->actingAs($admin)->post(route('tenant.leads.convert', $lead->id));

        Event::assertDispatched(LeadConverted::class, fn ($event) => $event->lead->id === $lead->id);
    }

    public function test_assignee_gets_an_in_app_notification_when_assigned_by_someone_else(): void
    {
        $tenant  = $this->setUpTenant();
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $target  = $this->makeUser($tenant, 'tenant_admin');
        $lead    = Lead::factory()->create(['tenant_id' => $tenant->id, 'assigned_to' => $admin->id]);

        $this->actingAs($admin)->post(route('tenant.leads.assign', $lead->id), [
            'assigned_to' => $target->id,
        ]);

        $this->assertDatabaseHas('notifications', [
            'tenant_id' => $tenant->id,
            'user_id'   => $target->id,
            'type'      => 'lead.assigned',
        ]);
    }

    public function test_assignee_does_not_get_notified_for_self_assignment(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        // Pre-assigned at creation (bypassing auto-assignment) so the only
        // LeadAssigned dispatch in this test is the explicit self-assign below.
        $lead   = Lead::factory()->create(['tenant_id' => $tenant->id, 'assigned_to' => $admin->id]);

        $this->actingAs($admin)->post(route('tenant.leads.assign', $lead->id), [
            'assigned_to' => $admin->id,
        ]);

        $this->assertSame(0, Notification::where('user_id', $admin->id)->where('type', 'lead.assigned')->count());
    }

    public function test_lead_created_fires_outbound_tenant_webhook(): void
    {
        Http::fake();

        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        TenantWebhook::create([
            'tenant_id'   => $tenant->id,
            'event'       => 'lead.created',
            'webhook_url' => 'https://example.test/hook',
            'is_active'   => true,
        ]);

        $this->actingAs($admin)->post(route('tenant.leads.store'), [
            'name'  => 'Webhook Lead',
            'phone' => '9123456789',
        ]);

        Http::assertSent(fn ($request) => $request->url() === 'https://example.test/hook'
            && $request['event'] === 'lead.created');
    }

    public function test_score_is_calculated_on_creation(): void
    {
        $tenant = $this->setUpTenant();
        $this->makeUser($tenant, 'tenant_admin');

        $lead = Lead::factory()->create([
            'tenant_id'  => $tenant->id,
            'source'     => 'referral',
            'email'      => 'lead@example.com',
            'company'    => 'Acme Inc',
            'lead_value' => 150000,
        ]);

        $lead->refresh();

        $this->assertGreaterThan(0, $lead->score);
        $this->assertLessThanOrEqual(100, $lead->score);
    }

    public function test_higher_value_source_scores_higher_than_cold_call(): void
    {
        $tenant = $this->setUpTenant();
        $this->makeUser($tenant, 'tenant_admin');

        $referral = Lead::factory()->create(['tenant_id' => $tenant->id, 'source' => 'referral', 'lead_value' => 0, 'email' => null, 'company' => null]);
        $coldCall = Lead::factory()->create(['tenant_id' => $tenant->id, 'source' => 'cold_call', 'lead_value' => 0, 'email' => null, 'company' => null]);

        $this->assertGreaterThan($coldCall->fresh()->score, $referral->fresh()->score);
    }

    public function test_score_recalculates_after_logging_a_call(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $lead   = Lead::factory()->create(['tenant_id' => $tenant->id]);
        $before = $lead->fresh()->score;

        $this->actingAs($admin)->post(route('tenant.leads.call-log.store', $lead->id), [
            'type'          => 'call',
            'description'   => 'Had a great first call',
            'call_outcome'  => 'connected',
            'call_duration' => 10,
        ]);

        $this->assertGreaterThanOrEqual($before, $lead->fresh()->score);
    }

    public function test_score_label_bands(): void
    {
        $tenant = $this->setUpTenant();
        $lead   = Lead::factory()->create(['tenant_id' => $tenant->id]);

        $lead->score = 80;
        $this->assertSame('Hot', $lead->score_label);

        $lead->score = 50;
        $this->assertSame('Warm', $lead->score_label);

        $lead->score = 10;
        $this->assertSame('Cold', $lead->score_label);
    }
}
