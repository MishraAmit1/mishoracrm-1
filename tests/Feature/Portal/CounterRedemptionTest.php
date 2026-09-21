<?php

namespace Tests\Feature\Portal;

use App\Models\Contact;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\LoyaltyCampaign;
use App\Models\LoyaltyCampaignRecipient;
use App\Models\LoyaltyTransaction;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LoyaltyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class CounterRedemptionTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function shop(array $rules = [], bool $loyalty = true, bool $portal = true): Tenant
    {
        $tenant   = $this->setUpTenant();
        $settings = $tenant->settings ?? [];
        $settings['modules']['loyalty']         = $loyalty;
        $settings['modules']['customer_portal'] = $portal;
        $settings['loyalty'] = array_replace(Tenant::LOYALTY_DEFAULTS, ['mode' => 'both', 'stamps_required' => 5, 'stamp_reward' => 'Free coffee'], $rules);
        $tenant->update(['settings' => $settings]);

        return $tenant->fresh();
    }

    private function customer(string $phone = '9876543210'): Customer
    {
        return Customer::create(['phone' => $phone]);
    }

    private function member(Tenant $tenant, ?Customer $customer = null, int $points = 0, string $phone = '9876543210'): Contact
    {
        $contact = Contact::create(['tenant_id' => $tenant->id, 'name' => 'Ravi', 'phone' => $phone]);

        if ($customer) {
            $contact->forceFill(['customer_id' => $customer->id, 'phone_verified' => true])->save();
        }
        if ($points > 0) {
            app(LoyaltyService::class)->manualAdjust($contact->fresh(), $points, 'seed');
        }

        return $contact->fresh();
    }

    private function admin(Tenant $tenant): User
    {
        return $this->makeUser($tenant, 'tenant_admin');
    }

    // Customer-guard only, exactly as in production (see WalletTest::as()).
    private function asCustomer(Customer $customer): static
    {
        $this->app['auth']->guard('customer')->setUser($customer);

        return $this;
    }

    // The relative signed url the customer's wallet would put in the QR.
    private function qrPayload(Tenant $tenant, Customer $customer): string
    {
        $this->asCustomer($customer);

        return $this->getJson(route('portal.wallet.qr', $tenant->id))->assertOk()->json('payload');
    }

    private function campaign(Tenant $tenant, array $overrides = []): LoyaltyCampaign
    {
        return LoyaltyCampaign::create(array_merge([
            'tenant_id' => $tenant->id, 'name' => 'Diwali 10', 'segment_type' => 'manual', 'segment_config' => [],
            'reward_type' => 'percent', 'reward_value' => 10, 'code_mode' => 'unique',
            'usage_limit_per_customer' => 1, 'delivery' => 'none', 'status' => 'active',
        ], $overrides));
    }

    private function offer(LoyaltyCampaign $campaign, Contact $contact, string $code = 'K7P2QF'): LoyaltyCampaignRecipient
    {
        return LoyaltyCampaignRecipient::create([
            'tenant_id' => $campaign->tenant_id, 'loyalty_campaign_id' => $campaign->id,
            'contact_id' => $contact->id, 'code' => $code,
        ]);
    }

    // ── Minting the wallet QR ──────────────────────────────────

    public function test_wallet_qr_is_a_short_signed_relative_url_without_the_phone_number(): void
    {
        $tenant   = $this->shop();
        $customer = $this->customer();
        $this->member($tenant, $customer, 100);

        $response = $this->asCustomer($customer)->getJson(route('portal.wallet.qr', $tenant->id))->assertOk();

        $payload = $response->json('payload');
        $this->assertStringStartsWith('/loyalty/counter/scan/' . $customer->id . '?', $payload);
        $this->assertStringContainsString('signature=', $payload);
        $this->assertStringContainsString('expires=', $payload);
        $this->assertStringNotContainsString('9876543210', $payload);
        $this->assertStringNotContainsString('http', $payload);
        $this->assertSame(90, $response->json('expires_in'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_wallet_qr_is_404_unless_the_customer_is_a_verified_member_of_a_portal_shop(): void
    {
        $customer = $this->customer();

        $unlinked   = $this->shop();
        $portalOff  = $this->shop([], true, false);
        $unverified = $this->shop();

        $this->member($portalOff, $customer, 50);
        $pending = $this->member($unverified, $customer, 50);
        $pending->forceFill(['phone_verified' => false])->save();

        foreach ([$unlinked, $portalOff, $unverified] as $tenant) {
            $this->asCustomer($customer)->getJson(route('portal.wallet.qr', $tenant->id))->assertNotFound();
        }
    }

    public function test_wallet_qr_needs_a_customer_login(): void
    {
        $tenant = $this->shop();

        $this->getJson(route('portal.wallet.qr', $tenant->id))->assertStatus(401);
    }

    public function test_the_wallet_page_draws_the_qr_box(): void
    {
        $tenant   = $this->shop();
        $customer = $this->customer();
        $this->member($tenant, $customer, 100);

        $this->asCustomer($customer)->get(route('portal.wallet.show', $tenant->id))
            ->assertOk()
            ->assertSee('Show at counter')
            ->assertSee('id="wallet-qr"', false)
            ->assertSee('js/wallet-qr.js', false);
    }

    // ── Scanning it at the counter ─────────────────────────────

    public function test_staff_scan_resolves_the_customers_card_in_their_own_shop(): void
    {
        $tenant   = $this->shop();
        $customer = $this->customer();
        $this->member($tenant, $customer, 340);
        $payload  = $this->qrPayload($tenant, $customer);

        $this->actingAs($this->admin($tenant))->getJson($payload)
            ->assertOk()
            ->assertJsonPath('contact.name', 'Ravi')
            ->assertJsonPath('contact.linked', true)
            ->assertJsonPath('card.points', 340)
            ->assertJsonPath('card.mode', 'both')
            ->assertJsonPath('card.stamps_required', 5);
    }

    public function test_a_qr_can_only_be_used_once(): void
    {
        $tenant   = $this->shop();
        $customer = $this->customer();
        $this->member($tenant, $customer, 10);
        $payload  = $this->qrPayload($tenant, $customer);
        $admin    = $this->admin($tenant);

        $this->actingAs($admin)->getJson($payload)->assertOk();
        $this->actingAs($admin)->getJson($payload)->assertStatus(410)->assertJsonPath('message', fn ($m) => str_contains($m, 'already used'));
    }

    public function test_an_expired_qr_is_rejected(): void
    {
        $tenant   = $this->shop();
        $customer = $this->customer();
        $this->member($tenant, $customer, 10);
        $payload  = $this->qrPayload($tenant, $customer);
        $admin    = $this->admin($tenant);

        $this->travel(2)->minutes();

        $this->actingAs($admin)->getJson($payload)->assertForbidden();
    }

    public function test_a_tampered_or_unsigned_qr_is_rejected(): void
    {
        $tenant   = $this->shop();
        $customer = $this->customer();
        $this->member($tenant, $customer, 10);
        $payload  = $this->qrPayload($tenant, $customer);
        $admin    = $this->admin($tenant);

        // Swap in a different customer id but keep the old signature.
        $forged = str_replace('/scan/' . $customer->id, '/scan/' . ($customer->id + 1), $payload);

        $this->actingAs($admin)->getJson($forged)->assertForbidden();
        $this->actingAs($admin)->getJson('/loyalty/counter/scan/' . $customer->id)->assertForbidden();
    }

    public function test_a_scan_never_resolves_another_shops_customer(): void
    {
        $shopA    = $this->shop();
        $shopB    = $this->shop();
        $customer = $this->customer();

        // Member of A only — with a big balance B must not be able to see.
        $this->member($shopA, $customer, 900);
        $payload = $this->qrPayload($shopA, $customer);

        $response = $this->actingAs($this->admin($shopB))->getJson($payload);

        $response->assertNotFound();
        $this->assertStringNotContainsString('900', $response->getContent());
        $this->assertStringNotContainsString('Ravi', $response->getContent());
    }

    public function test_a_customer_in_two_shops_resolves_to_each_shops_own_card(): void
    {
        $shopA    = $this->shop();
        $shopB    = $this->shop();
        $customer = $this->customer();
        $this->member($shopA, $customer, 111);
        $this->member($shopB, $customer, 222);

        $this->actingAs($this->admin($shopB))->getJson($this->qrPayload($shopA, $customer))
            ->assertOk()->assertJsonPath('card.points', 222);
    }

    public function test_an_unconfirmed_link_does_not_resolve_at_the_counter(): void
    {
        $tenant   = $this->shop();
        $customer = $this->customer();
        $contact  = $this->member($tenant, $customer, 100);
        $payload  = $this->qrPayload($tenant, $customer);
        $contact->forceFill(['phone_verified' => false])->save();

        $this->actingAs($this->admin($tenant))->getJson($payload)->assertNotFound();
    }

    // ── Access control ─────────────────────────────────────────

    public function test_counter_needs_the_stamp_or_manage_permission(): void
    {
        $tenant = $this->shop();
        $plain  = $this->makeUser($tenant, 'staff');

        $this->actingAs($plain)->get(route('tenant.loyalty.counter.index'))->assertForbidden();
        $this->actingAs($plain)->postJson(route('tenant.loyalty.counter.resolve'), ['phone' => '9876543210'])->assertForbidden();

        $cashier = $this->makeUser($tenant, 'staff', ['loyalty.stamp']);
        $this->actingAs($cashier)->get(route('tenant.loyalty.counter.index'))->assertOk();

        $manager = $this->makeUser($tenant, 'staff', ['loyalty.manage']);
        $this->actingAs($manager)->get(route('tenant.loyalty.counter.index'))->assertOk();
    }

    public function test_counter_is_403_when_the_portal_or_loyalty_module_is_off(): void
    {
        $portalOff  = $this->shop([], true, false);
        $loyaltyOff = $this->shop([], false, true);

        $this->actingAs($this->admin($portalOff))->get(route('tenant.loyalty.counter.index'))->assertForbidden();
        $this->actingAs($this->admin($loyaltyOff))->get(route('tenant.loyalty.counter.index'))->assertForbidden();
    }

    public function test_counter_page_renders_and_the_sidebar_links_to_it(): void
    {
        $tenant = $this->shop();
        $admin  = $this->admin($tenant);

        $this->actingAs($admin)->get(route('tenant.loyalty.counter.index'))
            ->assertOk()
            ->assertSee('Scan customer QR')
            ->assertSee('js/jsQR.js', false)
            ->assertSee('loyalty-counter-page.js', false)
            ->assertSee(route('tenant.loyalty.counter.index'), false);
    }

    public function test_the_sidebar_hides_the_counter_link_when_the_portal_is_off(): void
    {
        $tenant = $this->shop([], true, false);

        $this->actingAs($this->admin($tenant))->get(route('tenant.loyalty.index'))
            ->assertOk()
            ->assertDontSee(route('tenant.loyalty.counter.index'), false);
    }

    // ── Type-the-phone fallback ────────────────────────────────

    public function test_typing_a_phone_finds_the_contact_across_number_formats(): void
    {
        $tenant = $this->shop();
        $this->member($tenant, null, 60);
        $admin  = $this->admin($tenant);

        foreach (['9876543210', '+91 98765 43210', '98765-43210'] as $typed) {
            $this->actingAs($admin)->postJson(route('tenant.loyalty.counter.resolve'), ['phone' => $typed])
                ->assertOk()->assertJsonPath('card.points', 60)->assertJsonPath('contact.linked', false);
        }
    }

    public function test_typing_a_phone_only_finds_this_shops_contacts(): void
    {
        $shopA = $this->shop();
        $shopB = $this->shop();
        $this->member($shopA, null, 60);

        $this->actingAs($this->admin($shopB))->postJson(route('tenant.loyalty.counter.resolve'), ['phone' => '9876543210'])->assertNotFound();
    }

    public function test_typing_an_unknown_or_short_phone_is_a_clean_error(): void
    {
        $tenant = $this->shop();
        $admin  = $this->admin($tenant);

        $this->actingAs($admin)->postJson(route('tenant.loyalty.counter.resolve'), ['phone' => '9000000099'])->assertNotFound();
        $this->actingAs($admin)->postJson(route('tenant.loyalty.counter.resolve'), ['phone' => '123'])->assertStatus(422);
    }

    // ── +1 stamp ───────────────────────────────────────────────

    public function test_counter_adds_a_stamp_and_returns_the_fresh_card(): void
    {
        $tenant  = $this->shop();
        $contact = $this->member($tenant);

        $this->actingAs($this->admin($tenant))->postJson(route('tenant.loyalty.counter.stamp'), ['contact_id' => $contact->id])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('card.card.stamp_count', 1);

        $this->assertSame(1, $contact->fresh()->stamp_count);
    }

    public function test_a_second_stamp_the_same_day_is_a_clear_refusal(): void
    {
        $tenant  = $this->shop();
        $contact = $this->member($tenant);
        $admin   = $this->admin($tenant);

        $this->actingAs($admin)->postJson(route('tenant.loyalty.counter.stamp'), ['contact_id' => $contact->id])->assertOk();
        $this->actingAs($admin)->postJson(route('tenant.loyalty.counter.stamp'), ['contact_id' => $contact->id])
            ->assertStatus(422)->assertJsonPath('ok', false);

        $this->assertSame(1, $contact->fresh()->stamp_count);
    }

    public function test_cannot_stamp_another_shops_contact(): void
    {
        $shopA   = $this->shop();
        $shopB   = $this->shop();
        $contact = $this->member($shopA);

        $this->actingAs($this->admin($shopB))->postJson(route('tenant.loyalty.counter.stamp'), ['contact_id' => $contact->id])->assertNotFound();

        $this->assertSame(0, $contact->fresh()->stamp_count);
    }

    // ── Checkout: points / offer / reward on an on-the-spot invoice ──

    public function test_redeeming_points_raises_a_paid_invoice_and_tells_staff_the_cash_due(): void
    {
        $tenant  = $this->shop(['redeem_points_block' => 100, 'redeem_value' => 10, 'max_discount_percent' => 20]);
        $contact = $this->member($tenant, null, 500);
        $admin   = $this->admin($tenant);

        $response = $this->actingAs($admin)->postJson(route('tenant.loyalty.counter.checkout'), [
            'contact_id' => $contact->id, 'amount' => 1000, 'use_points' => true,
        ])->assertOk()->assertJsonPath('ok', true);

        $this->assertStringContainsString('Collect ₹950.00 cash', $response->json('message'));

        $invoice = Invoice::withoutGlobalScopes()->where('contact_id', $contact->id)->firstOrFail();
        $this->assertSame($invoice->number, $response->json('invoice_number'));
        $this->assertSame('paid', $invoice->status);
        $this->assertEquals(1000, $invoice->total);
        $this->assertEquals(50, $invoice->loyalty_discount);
        $this->assertEquals(500, $invoice->loyalty_points_redeemed);
        $this->assertEquals(950, $invoice->paid_amount);
        $this->assertDatabaseHas('invoice_payments', ['invoice_id' => $invoice->id, 'amount' => 950, 'method' => 'cash']);
        $this->assertDatabaseHas('loyalty_transactions', ['contact_id' => $contact->id, 'type' => 'redeem', 'points' => -500]);
    }

    public function test_cash_paid_at_the_counter_still_earns_points_and_a_stamp(): void
    {
        $tenant  = $this->shop(['redeem_points_block' => 100, 'redeem_value' => 10, 'max_discount_percent' => 20]);
        $contact = $this->member($tenant, null, 500);

        $this->actingAs($this->admin($tenant))->postJson(route('tenant.loyalty.counter.checkout'), [
            'contact_id' => $contact->id, 'amount' => 1000, 'use_points' => true,
        ])->assertOk();

        $fresh = $contact->fresh();
        $this->assertSame(9, $fresh->loyalty_points);    // 950 cash → 9 points (1 per ₹100)
        $this->assertSame(1, $fresh->stamp_count);       // one stamp for the paid visit
    }

    public function test_an_offer_is_applied_and_used_up(): void
    {
        $tenant   = $this->shop();
        $contact  = $this->member($tenant);
        $campaign = $this->campaign($tenant);
        $offer    = $this->offer($campaign, $contact);

        $response = $this->actingAs($this->admin($tenant))->postJson(route('tenant.loyalty.counter.checkout'), [
            'contact_id' => $contact->id, 'amount' => 500, 'offer_code' => 'k7p2qf',
        ])->assertOk();

        $invoice = Invoice::withoutGlobalScopes()->where('contact_id', $contact->id)->firstOrFail();
        $this->assertEquals(50, $invoice->campaign_discount);
        $this->assertEquals(450, $invoice->paid_amount);
        $this->assertStringContainsString('Collect ₹450.00 cash', $response->json('message'));
        $this->assertSame(1, $offer->fresh()->redeemed_count);
        $this->assertSame(1, $campaign->fresh()->redeemed_count);
    }

    public function test_points_and_an_offer_can_be_combined_on_one_bill(): void
    {
        $tenant   = $this->shop(['redeem_points_block' => 100, 'redeem_value' => 10, 'max_discount_percent' => 20]);
        $contact  = $this->member($tenant, null, 500);
        $this->offer($this->campaign($tenant), $contact);

        $this->actingAs($this->admin($tenant))->postJson(route('tenant.loyalty.counter.checkout'), [
            'contact_id' => $contact->id, 'amount' => 1000, 'use_points' => true, 'offer_code' => 'K7P2QF',
        ])->assertOk();

        $invoice = Invoice::withoutGlobalScopes()->where('contact_id', $contact->id)->firstOrFail();
        $this->assertEquals(50, $invoice->loyalty_discount);
        $this->assertGreaterThan(0, (float) $invoice->campaign_discount);
        $this->assertEquals(1000 - 50 - (float) $invoice->campaign_discount, (float) $invoice->paid_amount);
        $this->assertSame('paid', $invoice->status);
    }

    public function test_claiming_a_stamp_reward_needs_no_bill_amount(): void
    {
        $tenant  = $this->shop();
        $contact = $this->member($tenant);
        $contact->forceFill(['stamp_rewards_earned' => 1])->save();

        $response = $this->actingAs($this->admin($tenant))->postJson(route('tenant.loyalty.counter.checkout'), [
            'contact_id' => $contact->id, 'redeem_reward' => true,
        ])->assertOk();

        $this->assertStringContainsString('Free coffee', $response->json('message'));
        $this->assertStringContainsString('Nothing to collect', $response->json('message'));

        $fresh = $contact->fresh();
        $this->assertSame(0, $fresh->stamp_rewards_earned);
        $this->assertSame(0, $fresh->stamp_count); // a free visit earns no stamp

        $invoice = Invoice::withoutGlobalScopes()->where('contact_id', $contact->id)->firstOrFail();
        $this->assertSame('Free coffee', $invoice->loyalty_reward);
        $this->assertSame('paid', $invoice->status);
    }

    public function test_a_reward_claimed_alongside_a_paid_bill_still_earns_the_visits_stamp(): void
    {
        $tenant  = $this->shop();
        $contact = $this->member($tenant);
        $contact->forceFill(['stamp_rewards_earned' => 1])->save();

        $this->actingAs($this->admin($tenant))->postJson(route('tenant.loyalty.counter.checkout'), [
            'contact_id' => $contact->id, 'redeem_reward' => true, 'amount' => 300,
        ])->assertOk();

        $fresh = $contact->fresh();
        $this->assertSame(0, $fresh->stamp_rewards_earned);
        $this->assertSame(1, $fresh->stamp_count);
    }

    // ── Checkout failures leave nothing behind ─────────────────

    public function test_checkout_rejects_an_empty_request_and_a_missing_amount(): void
    {
        $tenant  = $this->shop();
        $contact = $this->member($tenant, null, 500);
        $admin   = $this->admin($tenant);

        $this->actingAs($admin)->postJson(route('tenant.loyalty.counter.checkout'), ['contact_id' => $contact->id])
            ->assertStatus(422)->assertJsonPath('ok', false);
        $this->actingAs($admin)->postJson(route('tenant.loyalty.counter.checkout'), ['contact_id' => $contact->id, 'use_points' => true])
            ->assertStatus(422)->assertJsonPath('message', 'Enter the bill amount first.');
        $this->actingAs($admin)->postJson(route('tenant.loyalty.counter.checkout'), ['contact_id' => $contact->id, 'use_points' => true, 'amount' => -5])
            ->assertStatus(422);

        $this->assertSame(0, Invoice::withoutGlobalScopes()->count());
    }

    public function test_a_failed_redemption_rolls_back_the_invoice_and_the_points(): void
    {
        $tenant  = $this->shop();
        $contact = $this->member($tenant, null, 0);           // nothing to redeem
        $admin   = $this->admin($tenant);

        $this->actingAs($admin)->postJson(route('tenant.loyalty.counter.checkout'), [
            'contact_id' => $contact->id, 'amount' => 500, 'use_points' => true,
        ])->assertStatus(422)->assertJsonPath('ok', false);

        $this->assertSame(0, Invoice::withoutGlobalScopes()->count());
    }

    public function test_a_bad_offer_code_after_points_rolls_the_points_back(): void
    {
        $tenant  = $this->shop(['redeem_points_block' => 100, 'redeem_value' => 10, 'max_discount_percent' => 20]);
        $contact = $this->member($tenant, null, 500);

        $this->actingAs($this->admin($tenant))->postJson(route('tenant.loyalty.counter.checkout'), [
            'contact_id' => $contact->id, 'amount' => 1000, 'use_points' => true, 'offer_code' => 'NOPE99',
        ])->assertStatus(422);

        $this->assertSame(500, $contact->fresh()->loyalty_points);
        $this->assertSame(0, Invoice::withoutGlobalScopes()->count());
        $this->assertSame(0, LoyaltyTransaction::withoutGlobalScopes()->where('type', 'redeem')->count());
    }

    public function test_claiming_a_reward_that_is_not_unlocked_is_refused_cleanly(): void
    {
        $tenant  = $this->shop();
        $contact = $this->member($tenant);

        $this->actingAs($this->admin($tenant))->postJson(route('tenant.loyalty.counter.checkout'), [
            'contact_id' => $contact->id, 'redeem_reward' => true,
        ])->assertStatus(422);

        $this->assertSame(0, Invoice::withoutGlobalScopes()->count());
    }

    public function test_an_offer_belonging_to_someone_else_is_refused(): void
    {
        $tenant  = $this->shop();
        $ravi    = $this->member($tenant, null, 0, '9876543210');
        $sunita  = $this->member($tenant, null, 0, '9123456789');
        $this->offer($this->campaign($tenant), $sunita, 'SUNITA');

        $this->actingAs($this->admin($tenant))->postJson(route('tenant.loyalty.counter.checkout'), [
            'contact_id' => $ravi->id, 'amount' => 500, 'offer_code' => 'SUNITA',
        ])->assertStatus(422);

        $this->assertSame(0, Invoice::withoutGlobalScopes()->count());
    }

    public function test_checkout_cannot_touch_another_shops_contact(): void
    {
        $shopA   = $this->shop();
        $shopB   = $this->shop();
        $contact = $this->member($shopA, null, 500);

        $this->actingAs($this->admin($shopB))->postJson(route('tenant.loyalty.counter.checkout'), [
            'contact_id' => $contact->id, 'amount' => 500, 'use_points' => true,
        ])->assertNotFound();

        $this->assertSame(500, $contact->fresh()->loyalty_points);
    }

    // ── Invoice page: Scan Customer ────────────────────────────

    private function openInvoice(Tenant $tenant, Contact $contact, string $status = 'sent'): Invoice
    {
        return Invoice::create([
            'tenant_id' => $tenant->id, 'contact_id' => $contact->id, 'number' => 'INV-T-' . uniqid(),
            'date' => now()->toDateString(), 'due_date' => now()->addDays(7)->toDateString(),
            'items' => [['description' => 'Item', 'quantity' => 1, 'rate' => 500]],
            'subtotal' => 500, 'total' => 500, 'paid_amount' => $status === 'paid' ? 500 : 0, 'status' => $status,
        ]);
    }

    public function test_invoice_page_offers_scan_customer_when_the_portal_is_on(): void
    {
        $tenant  = $this->shop();
        $contact = $this->member($tenant, null, 100);
        $invoice = $this->openInvoice($tenant, $contact);

        $this->actingAs($this->admin($tenant))->get(route('tenant.invoices.show', $invoice->id))
            ->assertOk()
            ->assertSee('Scan Customer')
            ->assertSee('id="inv-scan"', false)
            ->assertSee('data-contact="' . $contact->id . '"', false)
            ->assertSee('js/invoice-scan.js', false)
            ->assertSee(route('tenant.invoices.redeem_loyalty', $invoice->id), false)
            ->assertSee(route('tenant.invoices.apply_coupon', $invoice->id), false);
    }

    public function test_invoice_page_hides_scan_customer_when_off_or_settled(): void
    {
        $off        = $this->shop([], true, false);
        $offInvoice = $this->openInvoice($off, $this->member($off, null, 100));
        $this->actingAs($this->admin($off))->get(route('tenant.invoices.show', $offInvoice->id))
            ->assertOk()->assertDontSee('id="inv-scan"', false);

        $on          = $this->shop();
        $paidInvoice = $this->openInvoice($on, $this->member($on, null, 100), 'paid');
        $this->actingAs($this->admin($on))->get(route('tenant.invoices.show', $paidInvoice->id))
            ->assertOk()->assertDontSee('id="inv-scan"', false);
    }

    public function test_invoice_page_hides_scan_customer_from_staff_without_loyalty_permissions(): void
    {
        $tenant  = $this->shop();
        $contact = $this->member($tenant, null, 100);
        $invoice = $this->openInvoice($tenant, $contact);
        $staff   = $this->makeUser($tenant, 'staff', ['invoices.view_all']);

        $this->actingAs($staff)->get(route('tenant.invoices.show', $invoice->id))
            ->assertOk()->assertDontSee('id="inv-scan"', false);
    }
}
