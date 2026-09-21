<?php

namespace Tests\Feature\Portal;

use App\Models\Contact;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\LoyaltyTransaction;
use App\Models\Tenant;
use App\Services\LoyaltyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class LoyaltyOverviewTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function shop(array $rules = [], bool $portal = true): Tenant
    {
        $tenant   = $this->setUpTenant();
        $settings = $tenant->settings ?? [];
        $settings['modules']['loyalty']         = true;
        $settings['modules']['customer_portal'] = $portal;
        $settings['loyalty'] = array_replace(Tenant::LOYALTY_DEFAULTS, $rules);
        $tenant->update(['settings' => $settings]);

        return $tenant->fresh();
    }

    private function contact(Tenant $tenant, string $name = 'Ravi', string $phone = '9876543210'): Contact
    {
        return Contact::create(['tenant_id' => $tenant->id, 'name' => $name, 'phone' => $phone]);
    }

    private function paid(Tenant $tenant, ?Contact $contact, $paidAt, string $status = 'paid'): Invoice
    {
        return Invoice::create([
            'tenant_id' => $tenant->id, 'contact_id' => $contact?->id, 'number' => 'INV-' . uniqid(),
            'date' => now()->toDateString(), 'due_date' => now()->toDateString(),
            'items' => [['description' => 'Item', 'quantity' => 1, 'rate' => 100]],
            'subtotal' => 100, 'total' => 100, 'paid_amount' => $status === 'paid' ? 100 : 0,
            'status' => $status, 'paid_at' => $status === 'paid' ? $paidAt : null,
        ]);
    }

    private function overview(Tenant $tenant): array
    {
        return $this->actingAs($this->makeUser($tenant, 'tenant_admin'))
            ->get(route('tenant.loyalty.index'))->assertOk()->viewData('overview');
    }

    // ── Visit trend ────────────────────────────────────────────

    public function test_trend_has_thirty_days_and_counts_paid_visits_per_day(): void
    {
        $tenant  = $this->shop();
        $contact = $this->contact($tenant);

        $this->paid($tenant, $contact, now());
        $this->paid($tenant, $contact, now());
        $this->paid($tenant, $contact, now()->subDays(3));
        $this->paid($tenant, $contact, now()->subDays(29));

        $overview = $this->overview($tenant);
        $byDate   = $overview['trend']->pluck('count', 'date');

        $this->assertCount(30, $overview['trend']);
        $this->assertSame(now()->subDays(29)->toDateString(), $overview['trend']->first()['date']);
        $this->assertSame(now()->toDateString(), $overview['trend']->last()['date']);
        $this->assertSame(2, $byDate[now()->toDateString()]);
        $this->assertSame(1, $byDate[now()->subDays(3)->toDateString()]);
        $this->assertSame(1, $byDate[now()->subDays(29)->toDateString()]);
        $this->assertSame(4, $overview['visits']);
    }

    public function test_trend_ignores_old_unpaid_and_customerless_invoices_and_other_shops(): void
    {
        $tenant  = $this->shop();
        $other   = $this->shop();
        $contact = $this->contact($tenant);

        $this->paid($tenant, $contact, now()->subDays(45));            // outside the window
        $this->paid($tenant, $contact, now(), 'sent');                  // not paid
        $this->paid($tenant, null, now());                              // no customer
        $this->paid($other, $this->contact($other), now());             // someone else's shop

        $this->assertSame(0, $this->overview($tenant)['visits']);
    }

    public function test_the_page_shows_the_trend_card(): void
    {
        $tenant = $this->shop();
        $this->paid($tenant, $this->contact($tenant), now());

        $this->actingAs($this->makeUser($tenant, 'tenant_admin'))->get(route('tenant.loyalty.index'))
            ->assertOk()
            ->assertSee('Visits — last 30 days')
            ->assertSee('1 paid visit')
            ->assertSee('Recent activity');
    }

    // ── Recent activity ────────────────────────────────────────

    public function test_activity_lists_the_latest_twenty_entries_for_this_shop_only(): void
    {
        $tenant = $this->shop();
        $other  = $this->shop();
        $loyalty = app(LoyaltyService::class);

        $ravi = $this->contact($tenant, 'Ravi Kumar');
        for ($i = 1; $i <= 22; $i++) {
            $loyalty->manualAdjust($ravi->fresh(), $i, "seed {$i}");
        }
        $loyalty->manualAdjust($this->contact($other, 'Olga Other', '9111111111'), 50, 'other shop');

        $activity = $this->overview($tenant)['activity'];

        $this->assertCount(20, $activity);
        $this->assertSame('seed 22', $activity->first()->description);   // newest first
        $this->assertTrue($activity->every(fn ($tx) => (int) $tx->tenant_id === $tenant->id));

        $this->actingAs($this->makeUser($tenant, 'tenant_admin'))->get(route('tenant.loyalty.index'))
            ->assertSee('Ravi Kumar')
            ->assertDontSee('Olga Other');
    }

    public function test_stamp_rows_read_as_stamps_not_points_in_the_feed(): void
    {
        $tenant  = $this->shop(['mode' => 'stamps']);
        $contact = $this->contact($tenant);
        app(LoyaltyService::class)->awardStamp($contact->fresh(), null, 'Counter');

        $this->actingAs($this->makeUser($tenant, 'tenant_admin'))->get(route('tenant.loyalty.index'))
            ->assertSee('+1 stamp')
            ->assertSee('Counter stamp');
    }

    // ── Portal / stamp numbers ─────────────────────────────────

    public function test_portal_numbers_show_only_when_the_portal_is_on(): void
    {
        $on = $this->shop(['mode' => 'points'], true);
        $customer = Customer::create(['phone' => '9876543210']);
        $linked   = $this->contact($on);
        $linked->forceFill(['customer_id' => $customer->id, 'phone_verified' => true])->save();
        $pending  = $this->contact($on, 'Pending', '9123456789');
        $pending->forceFill(['customer_id' => Customer::create(['phone' => '9123456789'])->id, 'phone_verified' => false])->save();

        $portal = $this->overview($on)['portal'];
        $this->assertSame(1, $portal['linked']);
        $this->assertSame(1, $portal['pending']);
        $this->assertNull($portal['stamps']);                      // points-only shop: no stamp block

        $this->actingAs($this->makeUser($on, 'tenant_admin'))->get(route('tenant.loyalty.index'))
            ->assertSee('Wallet customers')->assertSee('Awaiting confirmation')->assertDontSee('Active stamp cards');

        $off = $this->shop([], false);
        $this->assertNull($this->overview($off)['portal']);
        $this->actingAs($this->makeUser($off, 'tenant_admin'))->get(route('tenant.loyalty.index'))
            ->assertDontSee('Wallet customers');
    }

    public function test_stamp_numbers_count_cards_and_rewards(): void
    {
        $tenant = $this->shop(['mode' => 'both', 'stamps_required' => 3]);

        $a = $this->contact($tenant, 'A', '9000000001');
        $b = $this->contact($tenant, 'B', '9000000002');
        $c = $this->contact($tenant, 'C', '9000000003');

        $loyalty = app(LoyaltyService::class);
        $nextDay = fn () => LoyaltyTransaction::withoutGlobalScopes()->update(['created_at' => now()->subDays(2)]);

        // One stamp a day each, so age the ledger between rounds.
        $loyalty->awardStamp($a->fresh());
        $loyalty->awardStamp($b->fresh());
        $nextDay();
        $loyalty->awardStamp($a->fresh());
        $loyalty->awardStamp($b->fresh());
        $nextDay();
        $loyalty->awardStamp($b->fresh());        // B's third stamp completes the card...
        $loyalty->redeemStampReward($b->fresh()); // ...and B claims the reward
        $loyalty->awardStamp($c->fresh());        // C: one stamp

        $stamps = $this->overview($tenant)['portal']['stamps'];

        $this->assertSame(2, $stamps['active_cards']);  // A (2 stamps) and C (1); B is back to 0
        $this->assertSame(0, $stamps['unclaimed']);
        $this->assertSame(1, $stamps['unlocked']);
        $this->assertSame(1, $stamps['claimed']);

        $this->actingAs($this->makeUser($tenant, 'tenant_admin'))->get(route('tenant.loyalty.index'))
            ->assertSee('Active stamp cards')
            ->assertSee('Rewards unclaimed');
    }

    public function test_unclaimed_rewards_are_summed_across_customers(): void
    {
        $tenant = $this->shop(['mode' => 'stamps']);
        $this->contact($tenant, 'A', '9000000001')->forceFill(['stamp_rewards_earned' => 2])->save();
        $this->contact($tenant, 'B', '9000000002')->forceFill(['stamp_rewards_earned' => 1])->save();

        $this->assertSame(3, $this->overview($tenant)['portal']['stamps']['unclaimed']);
    }

    public function test_the_loyalty_page_links_to_the_qr_kit_only_when_the_portal_is_on(): void
    {
        $on  = $this->shop([], true);
        $off = $this->shop([], false);

        $this->actingAs($this->makeUser($on, 'tenant_admin'))->get(route('tenant.loyalty.index'))
            ->assertSee(route('tenant.loyalty.qr-kit.index'), false);
        $this->actingAs($this->makeUser($off, 'tenant_admin'))->get(route('tenant.loyalty.index'))
            ->assertDontSee(route('tenant.loyalty.qr-kit.index'), false);
    }
}
