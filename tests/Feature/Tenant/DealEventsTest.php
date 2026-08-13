<?php

namespace Tests\Feature\Tenant;

use App\Events\DealAssigned;
use App\Events\DealCreated;
use App\Events\DealLost;
use App\Events\DealStageChanged;
use App\Events\DealWon;
use App\Models\ApiKey;
use App\Models\Deal;
use App\Models\Notification;
use App\Models\Quotation;
use App\Models\TenantWebhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class DealEventsTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function makePendingQuotation(int $tenantId, int $dealId): Quotation
    {
        return Quotation::create([
            'tenant_id' => $tenantId,
            'deal_id'   => $dealId,
            'number'    => 'QT-TEST-' . $dealId,
            'date'      => now()->toDateString(),
            'items'     => [],
            'subtotal'  => 1000,
            'total'     => 1000,
            'status'    => 'sent',
        ]);
    }

    public function test_creating_a_deal_dispatches_deal_created(): void
    {
        $tenant = $this->setUpTenant();
        $this->makeUser($tenant, 'tenant_admin');
        Event::fake([DealCreated::class]);

        Deal::factory()->create(['tenant_id' => $tenant->id]);

        Event::assertDispatched(DealCreated::class);
    }

    public function test_stage_change_dispatches_deal_stage_changed_with_old_and_new_values(): void
    {
        $tenant = $this->setUpTenant();
        $this->makeUser($tenant, 'tenant_admin');
        $deal = Deal::factory()->create(['tenant_id' => $tenant->id, 'stage' => 'new']);

        Event::fake([DealStageChanged::class]);
        $deal->update(['stage' => 'proposal']);

        Event::assertDispatched(DealStageChanged::class, function ($event) use ($deal) {
            return $event->deal->id === $deal->id
                && $event->oldStage === 'new'
                && $event->newStage === 'proposal';
        });
    }

    public function test_marking_won_dispatches_deal_won(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $deal   = Deal::factory()->create(['tenant_id' => $tenant->id, 'stage' => 'negotiation']);

        Event::fake([DealStageChanged::class, DealWon::class]);

        $this->actingAs($admin)->post(route('tenant.deals.mark_won', $deal->id));

        Event::assertDispatched(DealWon::class, fn ($event) => $event->deal->id === $deal->id);
    }

    public function test_marking_lost_dispatches_deal_lost(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $deal   = Deal::factory()->create(['tenant_id' => $tenant->id, 'stage' => 'proposal']);

        Event::fake([DealStageChanged::class, DealLost::class]);

        $this->actingAs($admin)->post(route('tenant.deals.mark_lost', $deal->id), [
            'lost_reason' => 'No budget',
        ]);

        Event::assertDispatched(DealLost::class, fn ($event) => $event->deal->id === $deal->id);
    }

    public function test_assigning_a_deal_dispatches_deal_assigned(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $target = $this->makeUser($tenant, 'tenant_admin');
        $deal   = Deal::factory()->create(['tenant_id' => $tenant->id, 'assigned_to' => null]);

        Event::fake([DealAssigned::class]);

        $this->actingAs($admin)->put(route('tenant.deals.update', $deal->id), [
            'title'       => $deal->title,
            'value'       => $deal->value,
            'stage'       => $deal->stage,
            'assigned_to' => $target->id,
        ]);

        Event::assertDispatched(DealAssigned::class, fn ($event) => $event->deal->id === $deal->id);
    }

    public function test_assignee_gets_an_in_app_notification_when_marked_won(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $deal   = Deal::factory()->create(['tenant_id' => $tenant->id, 'assigned_to' => $admin->id, 'stage' => 'negotiation']);

        $this->actingAs($admin)->post(route('tenant.deals.mark_won', $deal->id));

        $this->assertDatabaseHas('notifications', [
            'tenant_id' => $tenant->id,
            'user_id'   => $admin->id,
            'type'      => 'deal.won',
        ]);
    }

    public function test_deal_won_fires_outbound_tenant_webhook(): void
    {
        Http::fake();

        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $deal   = Deal::factory()->create(['tenant_id' => $tenant->id, 'stage' => 'negotiation']);

        TenantWebhook::create([
            'tenant_id'   => $tenant->id,
            'event'       => 'deal.won',
            'webhook_url' => 'https://example.test/hook',
            'is_active'   => true,
        ]);

        $this->actingAs($admin)->post(route('tenant.deals.mark_won', $deal->id));

        Http::assertSent(fn ($request) => $request->url() === 'https://example.test/hook'
            && $request['event'] === 'deal.won');
    }

    public function test_marking_won_via_web_auto_accepts_latest_pending_quotation(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $deal   = Deal::factory()->create(['tenant_id' => $tenant->id, 'stage' => 'negotiation']);
        $quotation = $this->makePendingQuotation($tenant->id, $deal->id);

        $this->actingAs($admin)->post(route('tenant.deals.mark_won', $deal->id));

        $this->assertSame('accepted', $quotation->fresh()->status);
    }

    public function test_marking_won_via_api_also_auto_accepts_latest_pending_quotation(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $apiKey = ApiKey::generate($tenant->id, $admin->id, 'test key');
        $deal   = Deal::factory()->create(['tenant_id' => $tenant->id, 'stage' => 'negotiation']);
        $quotation = $this->makePendingQuotation($tenant->id, $deal->id);

        $this->withHeader('X-API-Key', $apiKey->key)
            ->postJson('/api/v1/tenant/deals/' . $deal->id . '/mark-won')
            ->assertOk();

        $this->assertSame('accepted', $quotation->fresh()->status);
    }
}
