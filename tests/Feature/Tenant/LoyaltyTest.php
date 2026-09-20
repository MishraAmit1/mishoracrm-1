<?php

namespace Tests\Feature\Tenant;

use App\Models\ApiKey;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\LoyaltyCampaign;
use App\Models\LoyaltyOtp;
use App\Models\LoyaltyTransaction;
use App\Models\Tenant;
use App\Models\WhatsappSetting;
use App\Services\LoyaltyCampaignService;
use App\Services\LoyaltyService;
use App\Services\WhatsappChatbotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class LoyaltyTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function enableLoyalty(Tenant $tenant, array $rules = []): Tenant
    {
        $settings = $tenant->settings ?? [];
        $settings['modules']['loyalty'] = true;
        if ($rules) {
            $settings['loyalty'] = array_replace(Tenant::LOYALTY_DEFAULTS, $rules);
        }
        $tenant->update(['settings' => $settings]);

        return $tenant->fresh();
    }

    private function contact(Tenant $tenant): Contact
    {
        return Contact::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Repeat Customer',
            'phone'     => '9876543210',
        ]);
    }

    private function invoice(Tenant $tenant, Contact $contact, float $total, string $status = 'sent'): Invoice
    {
        return Invoice::create([
            'tenant_id'   => $tenant->id,
            'contact_id'  => $contact->id,
            'number'      => Invoice::generateNumber($tenant->id),
            'date'        => now()->toDateString(),
            'due_date'    => now()->addDays(7)->toDateString(),
            'items'       => [['description' => 'Item', 'quantity' => 1, 'rate' => $total]],
            'subtotal'    => $total,
            'total'       => $total,
            'paid_amount' => 0,
            'status'      => $status,
        ]);
    }

    private function payInFull($admin, Invoice $invoice): void
    {
        $this->actingAs($admin)->post(route('tenant.invoices.record_payment', $invoice->id), [
            'payments' => [[
                'amount'  => (float) $invoice->due_amount,
                'method'  => 'cash',
                'paid_at' => now()->toDateString(),
            ]],
        ])->assertRedirect();
    }

    private function givePoints(Contact $contact, int $points): void
    {
        app(LoyaltyService::class)->manualAdjust($contact->fresh(), $points, 'test seed');
    }

    // ── Module gating ───────────────────────────────────────────

    public function test_loyalty_routes_are_403_when_the_module_is_off(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $this->actingAs($admin)->get(route('tenant.loyalty.index'))->assertForbidden();
    }

    public function test_index_loads_once_the_module_is_enabled(): void
    {
        $tenant = $this->setUpTenant();
        $tenant = $this->enableLoyalty($tenant);
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $this->actingAs($admin)->get(route('tenant.loyalty.index'))->assertOk();
    }

    // ── Settings ────────────────────────────────────────────────

    public function test_owner_can_save_rules(): void
    {
        $tenant = $this->enableLoyalty($this->setUpTenant());
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $this->actingAs($admin)->post(route('tenant.loyalty.settings.update'), [
            'points_per_amount'      => 2,
            'amount_per_point_block' => 50,
            'redeem_points_block'    => 100,
            'redeem_value'           => 5,
            'min_discount'           => 0,
            'max_discount_percent'   => 15,
            'expiry_months'          => 6,
            'max_points_per_day'     => 1000,
            'tiers'                  => ['bronze' => 0, 'silver' => 500, 'gold' => 5000],
        ])->assertRedirect();

        $this->assertSame(2, $tenant->fresh()->loyaltySettings()['points_per_amount']);
        $this->assertSame(500, $tenant->fresh()->loyaltySettings()['tiers']['silver']);
    }

    public function test_rules_reject_non_increasing_tiers(): void
    {
        $tenant = $this->enableLoyalty($this->setUpTenant());
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $this->actingAs($admin)->post(route('tenant.loyalty.settings.update'), [
            'points_per_amount'      => 1,
            'amount_per_point_block' => 100,
            'redeem_points_block'    => 100,
            'redeem_value'           => 10,
            'min_discount'           => 0,
            'max_discount_percent'   => 20,
            'expiry_months'          => 12,
            'max_points_per_day'     => 500,
            'tiers'                  => ['bronze' => 0, 'silver' => 5000, 'gold' => 1000],
        ])->assertSessionHasErrors('tiers.silver');
    }

    // ── Earning ─────────────────────────────────────────────────

    public function test_full_payment_awards_points_and_sets_tier(): void
    {
        $tenant  = $this->enableLoyalty($this->setUpTenant(), ['tiers' => ['bronze' => 0, 'silver' => 20, 'gold' => 100]]);
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = $this->contact($tenant);
        $invoice = $this->invoice($tenant, $contact, 2500);

        $this->payInFull($admin, $invoice);

        $contact->refresh();
        $this->assertSame(25, $contact->loyalty_points);          // 2500 / 100
        $this->assertSame(25, $contact->loyalty_lifetime_points);
        $this->assertSame('silver', $contact->loyalty_tier);      // 25 >= silver(20)
        $this->assertNotNull($contact->loyalty_updated_at);

        $earn = LoyaltyTransaction::where('contact_id', $contact->id)->where('type', 'earn')->first();
        $this->assertNotNull($earn);
        $this->assertSame(25, $earn->remaining_points);
        $this->assertTrue($earn->earn_expires_at->between(now()->addMonths(11), now()->addMonths(13)));
    }

    public function test_partial_payment_awards_nothing_and_completing_it_awards_once(): void
    {
        $tenant  = $this->enableLoyalty($this->setUpTenant());
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = $this->contact($tenant);
        $invoice = $this->invoice($tenant, $contact, 1000);

        $this->actingAs($admin)->post(route('tenant.invoices.record_payment', $invoice->id), [
            'payments' => [['amount' => 400, 'method' => 'cash', 'paid_at' => now()->toDateString()]],
        ])->assertRedirect();

        $this->assertSame(0, $contact->fresh()->loyalty_points);

        $this->actingAs($admin)->post(route('tenant.invoices.record_payment', $invoice->id), [
            'payments' => [['amount' => 600, 'method' => 'cash', 'paid_at' => now()->toDateString()]],
        ])->assertRedirect();

        $this->assertSame(10, $contact->fresh()->loyalty_points);

        // Re-marking paid must not double award.
        $this->actingAs($admin)->post(route('tenant.invoices.update_status', $invoice->id), ['status' => 'paid'])->assertRedirect();
        $this->assertSame(10, $contact->fresh()->loyalty_points);
        $this->assertSame(1, LoyaltyTransaction::where('contact_id', $contact->id)->where('type', 'earn')->count());
    }

    public function test_daily_cap_limits_points_earned(): void
    {
        $tenant  = $this->enableLoyalty($this->setUpTenant(), ['max_points_per_day' => 30]);
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = $this->contact($tenant);

        $this->payInFull($admin, $this->invoice($tenant, $contact, 2000)); // wants 20
        $this->payInFull($admin, $this->invoice($tenant, $contact, 2000)); // wants 20, capped to 10

        $this->assertSame(30, $contact->fresh()->loyalty_points);
    }

    public function test_reversing_a_paid_invoice_claws_back_points(): void
    {
        $tenant  = $this->enableLoyalty($this->setUpTenant());
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = $this->contact($tenant);
        $invoice = $this->invoice($tenant, $contact, 3000);

        $this->payInFull($admin, $invoice);
        $this->assertSame(30, $contact->fresh()->loyalty_points);

        $this->actingAs($admin)->post(route('tenant.invoices.update_status', $invoice->id), ['status' => 'sent'])->assertRedirect();

        $contact->refresh();
        $this->assertSame(0, $contact->loyalty_points);
        $this->assertSame(0, $contact->loyalty_lifetime_points);
    }

    // ── Redeeming ───────────────────────────────────────────────

    public function test_redeem_consumes_oldest_lots_and_keeps_tier(): void
    {
        $tenant  = $this->enableLoyalty($this->setUpTenant());
        $contact = $this->contact($tenant);
        $svc     = app(LoyaltyService::class);

        $lot1 = $svc->manualAdjust($contact, 100, 'seed 1');
        $lot2 = $svc->manualAdjust($contact->fresh(), 100, 'seed 2');

        $svc->redeem($contact->fresh(), 120, 'Redeemed at billing');

        $this->assertSame(80, $contact->fresh()->loyalty_points);
        $this->assertSame(200, $contact->fresh()->loyalty_lifetime_points); // unchanged
        $this->assertSame(0, $lot1->fresh()->remaining_points);
        $this->assertSame(80, $lot2->fresh()->remaining_points);
    }

    public function test_redeem_more_than_balance_throws(): void
    {
        $tenant  = $this->enableLoyalty($this->setUpTenant());
        $contact = $this->contact($tenant);
        $svc     = app(LoyaltyService::class);
        $svc->manualAdjust($contact, 50, 'seed');

        $this->expectException(\RuntimeException::class);
        $svc->redeem($contact->fresh(), 100, 'too much');
    }

    // ── Manual adjustment ───────────────────────────────────────

    public function test_manual_negative_adjust_cannot_go_below_zero(): void
    {
        $tenant  = $this->enableLoyalty($this->setUpTenant());
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = $this->contact($tenant);
        app(LoyaltyService::class)->manualAdjust($contact, 40, 'seed');

        $this->actingAs($admin)->post(route('tenant.loyalty.adjust', $contact->id), [
            'points' => -100,
            'reason' => 'Goodwill correction',
        ])->assertRedirect();

        $this->assertSame(0, $contact->fresh()->loyalty_points);
    }

    public function test_adjust_requires_a_reason(): void
    {
        $tenant  = $this->enableLoyalty($this->setUpTenant());
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = $this->contact($tenant);

        $this->actingAs($admin)->post(route('tenant.loyalty.adjust', $contact->id), [
            'points' => 50,
        ])->assertSessionHasErrors('reason');
    }

    // ── Expiry ──────────────────────────────────────────────────

    public function test_expire_due_points_only_touches_lapsed_lots(): void
    {
        $tenant  = $this->enableLoyalty($this->setUpTenant());
        $contact = $this->contact($tenant);
        $svc     = app(LoyaltyService::class);

        $old = $svc->manualAdjust($contact, 100, 'old');
        $svc->manualAdjust($contact->fresh(), 50, 'fresh');

        $old->update(['earn_expires_at' => now()->subDay()]);

        $expired = $svc->expireDuePoints($tenant->id);

        $this->assertSame(1, $expired);
        $this->assertSame(50, $contact->fresh()->loyalty_points);
        $this->assertSame(150, $contact->fresh()->loyalty_lifetime_points); // lifetime untouched
        $this->assertSame(1, LoyaltyTransaction::where('contact_id', $contact->id)->where('type', 'expire')->count());
    }

    public function test_expire_command_runs(): void
    {
        $tenant  = $this->enableLoyalty($this->setUpTenant());
        $contact = $this->contact($tenant);
        $lot = app(LoyaltyService::class)->manualAdjust($contact, 100, 'old');
        $lot->update(['earn_expires_at' => now()->subDay()]);

        $this->artisan('loyalty:expire-points')->assertSuccessful();

        $this->assertSame(0, $contact->fresh()->loyalty_points);
    }

    // ── Isolation ───────────────────────────────────────────────

    public function test_cannot_adjust_a_contact_from_another_tenant(): void
    {
        $tenantA = $this->enableLoyalty($this->setUpTenant());
        $adminA  = $this->makeUser($tenantA, 'tenant_admin');
        $tenantB = Tenant::factory()->create();
        $contactB = $this->contact($tenantB);

        $this->actingAs($adminA)->post(route('tenant.loyalty.adjust', $contactB->id), [
            'points' => 50, 'reason' => 'x',
        ])->assertNotFound();
    }

    public function test_staff_without_permission_cannot_open_loyalty(): void
    {
        $tenant = $this->enableLoyalty($this->setUpTenant());
        $staff  = $this->makeUser($tenant, 'staff');

        $this->actingAs($staff)->get(route('tenant.loyalty.index'))->assertForbidden();
    }

    // ── Phase 2: redemption against an invoice ──────────────────

    public function test_redeeming_points_reduces_the_balance_due(): void
    {
        $tenant  = $this->enableLoyalty($this->setUpTenant());
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = $this->contact($tenant);
        $this->givePoints($contact, 500);
        $invoice = $this->invoice($tenant, $contact, 1000);

        $this->actingAs($admin)->post(route('tenant.invoices.redeem_loyalty', $invoice->id), [
            'points' => 200,
        ])->assertRedirect();

        $invoice->refresh();
        $this->assertSame(200, $invoice->loyalty_points_redeemed);
        $this->assertEqualsWithDelta(20.0, (float) $invoice->loyalty_discount, 0.01); // 200 pts = ₹20
        $this->assertEqualsWithDelta(980.0, $invoice->due_amount, 0.01);
        $this->assertSame(300, $contact->fresh()->loyalty_points);
    }

    public function test_use_max_is_capped_by_the_per_bill_policy(): void
    {
        $tenant  = $this->enableLoyalty($this->setUpTenant(), ['max_discount_percent' => 20]);
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = $this->contact($tenant);
        $this->givePoints($contact, 100000);
        $invoice = $this->invoice($tenant, $contact, 1000);

        $this->actingAs($admin)->post(route('tenant.invoices.redeem_loyalty', $invoice->id), [
            'use_max' => 1,
        ])->assertRedirect();

        // 20% of ₹1000 = ₹200 = 2000 points
        $this->assertEqualsWithDelta(200.0, (float) $invoice->fresh()->loyalty_discount, 0.01);
        $this->assertSame(2000, $invoice->fresh()->loyalty_points_redeemed);
    }

    public function test_redeeming_the_whole_bill_marks_it_paid(): void
    {
        $tenant  = $this->enableLoyalty($this->setUpTenant(), ['max_discount_percent' => 100]);
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = $this->contact($tenant);
        $this->givePoints($contact, 20000);
        $invoice = $this->invoice($tenant, $contact, 1000);

        $this->actingAs($admin)->post(route('tenant.invoices.redeem_loyalty', $invoice->id), [
            'use_max' => 1,
        ])->assertRedirect();

        $invoice->refresh();
        $this->assertSame('paid', $invoice->status);
        $this->assertEqualsWithDelta(0.0, $invoice->due_amount, 0.01);
    }

    public function test_removing_a_redemption_returns_the_points(): void
    {
        $tenant  = $this->enableLoyalty($this->setUpTenant());
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = $this->contact($tenant);
        $this->givePoints($contact, 500);
        $lifetimeBefore = $contact->fresh()->loyalty_lifetime_points;
        $invoice = $this->invoice($tenant, $contact, 1000);

        $this->actingAs($admin)->post(route('tenant.invoices.redeem_loyalty', $invoice->id), ['points' => 300])->assertRedirect();
        $this->assertSame(200, $contact->fresh()->loyalty_points);

        $this->actingAs($admin)->post(route('tenant.invoices.unredeem_loyalty', $invoice->id))->assertRedirect();

        $contact->refresh();
        $this->assertSame(500, $contact->loyalty_points);
        $this->assertSame($lifetimeBefore, $contact->loyalty_lifetime_points); // reversal doesn't re-count lifetime
        $this->assertSame(0, $invoice->fresh()->loyalty_points_redeemed);
        $this->assertEqualsWithDelta(0.0, (float) $invoice->fresh()->loyalty_discount, 0.01);
    }

    public function test_invoice_with_active_redemption_cannot_be_edited(): void
    {
        $tenant  = $this->enableLoyalty($this->setUpTenant());
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = $this->contact($tenant);
        $this->givePoints($contact, 500);
        $invoice = $this->invoice($tenant, $contact, 1000);

        $this->actingAs($admin)->post(route('tenant.invoices.redeem_loyalty', $invoice->id), ['points' => 100])->assertRedirect();

        $this->actingAs($admin)->get(route('tenant.invoices.edit', $invoice->id))
            ->assertRedirect(route('tenant.invoices.show', $invoice->id));
    }

    public function test_deleting_an_invoice_returns_redeemed_points(): void
    {
        $tenant  = $this->enableLoyalty($this->setUpTenant());
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = $this->contact($tenant);
        $this->givePoints($contact, 500);
        $invoice = $this->invoice($tenant, $contact, 1000);

        $this->actingAs($admin)->post(route('tenant.invoices.redeem_loyalty', $invoice->id), ['points' => 300])->assertRedirect();
        $this->actingAs($admin)->delete(route('tenant.invoices.destroy', $invoice->id))->assertRedirect();

        $this->assertSame(500, $contact->fresh()->loyalty_points);
    }

    public function test_cash_payment_after_a_redemption_settles_the_invoice_and_earns_on_cash(): void
    {
        $tenant  = $this->enableLoyalty($this->setUpTenant());
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = $this->contact($tenant);
        $this->givePoints($contact, 500);
        $invoice = $this->invoice($tenant, $contact, 1000);

        $this->actingAs($admin)->post(route('tenant.invoices.redeem_loyalty', $invoice->id), ['points' => 200])->assertRedirect(); // ₹20 off, ₹980 due

        $this->actingAs($admin)->post(route('tenant.invoices.record_payment', $invoice->id), [
            'payments' => [['amount' => 980, 'method' => 'cash', 'paid_at' => now()->toDateString()]],
        ])->assertRedirect();

        $invoice->refresh();
        $this->assertSame('paid', $invoice->status);
        // earned on ₹980 cash → 9 points (was 300 after redeeming 200 of 500)
        $this->assertSame(309, $contact->fresh()->loyalty_points);
    }

    public function test_redeem_needs_the_loyalty_manage_permission(): void
    {
        $tenant  = $this->enableLoyalty($this->setUpTenant());
        $staff   = $this->makeUser($tenant, 'staff');
        $contact = $this->contact($tenant);
        $this->givePoints($contact, 500);
        $invoice = $this->invoice($tenant, $contact, 1000);

        $this->actingAs($staff)->post(route('tenant.invoices.redeem_loyalty', $invoice->id), ['points' => 100])
            ->assertForbidden();
    }

    public function test_min_discount_blocks_a_tiny_redemption(): void
    {
        $tenant  = $this->enableLoyalty($this->setUpTenant(), ['min_discount' => 50]);
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = $this->contact($tenant);
        $this->givePoints($contact, 100); // worth only ₹10
        $invoice = $this->invoice($tenant, $contact, 1000);

        $this->actingAs($admin)->post(route('tenant.invoices.redeem_loyalty', $invoice->id), ['use_max' => 1])
            ->assertRedirect();

        $this->assertSame(0, $invoice->fresh()->loyalty_points_redeemed);
        $this->assertSame(100, $contact->fresh()->loyalty_points);
    }

    public function test_counter_lookup_finds_a_customer_by_phone(): void
    {
        $tenant  = $this->enableLoyalty($this->setUpTenant());
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = $this->contact($tenant);
        $this->givePoints($contact, 340);

        $this->actingAs($admin)->get(route('tenant.loyalty.lookup', ['q' => '9876543210']))
            ->assertOk()
            ->assertSee('Repeat Customer')
            ->assertSee('340');
    }

    // ── Phase 3: birthday / referral / win-back ─────────────────

    public function test_new_contact_gets_a_referral_code(): void
    {
        $tenant = $this->setUpTenant();
        $c      = $this->contact($tenant);

        $this->assertMatchesRegularExpression('/^[A-Z2-9]{6}$/', $c->referral_code);
    }

    public function test_referral_rewards_both_sides_once(): void
    {
        $tenant   = $this->enableLoyalty($this->setUpTenant(), ['referral_bonus_points' => 100]);
        $admin    = $this->makeUser($tenant, 'tenant_admin', ['contacts.create', 'contacts.edit_all']);
        $referrer = $this->contact($tenant);

        $this->actingAs($admin)->post(route('tenant.contacts.store'), [
            'name'             => 'New Friend',
            'phone'            => '9000000001',
            'referred_by_code' => $referrer->referral_code,
        ])->assertRedirect();

        $referee = Contact::where('phone', '9000000001')->first();
        $this->assertSame($referrer->id, $referee->referred_by_contact_id);
        $this->assertSame(100, $referee->loyalty_points);
        $this->assertSame(100, $referrer->fresh()->loyalty_points);

        // Editing the referee again must not re-award.
        $this->actingAs($admin)->put(route('tenant.contacts.update', $referee->id), [
            'name'             => 'New Friend',
            'phone'            => '9000000001',
            'referred_by_code' => $referrer->referral_code,
        ])->assertRedirect();

        $this->assertSame(100, $referrer->fresh()->loyalty_points);
    }

    public function test_referral_does_nothing_when_bonus_is_zero(): void
    {
        $tenant   = $this->enableLoyalty($this->setUpTenant()); // referral_bonus_points defaults to 0
        $admin    = $this->makeUser($tenant, 'tenant_admin', ['contacts.create']);
        $referrer = $this->contact($tenant);

        $this->actingAs($admin)->post(route('tenant.contacts.store'), [
            'name' => 'Friend', 'phone' => '9000000002', 'referred_by_code' => $referrer->referral_code,
        ])->assertRedirect();

        $referee = Contact::where('phone', '9000000002')->first();
        $this->assertSame($referrer->id, $referee->referred_by_contact_id); // still linked
        $this->assertSame(0, $referee->loyalty_points);                      // but no points
    }

    public function test_birthday_bonus_is_granted_once_a_year(): void
    {
        $tenant  = $this->enableLoyalty($this->setUpTenant(), ['birthday_bonus_points' => 150]);
        $contact = $this->contact($tenant);
        $contact->update(['birthday' => now()->subYears(30)->toDateString()]);

        $this->artisan('loyalty:occasion-offers')->assertSuccessful();

        $contact->refresh();
        $this->assertSame(150, $contact->loyalty_points);
        $this->assertSame(now()->toDateString(), $contact->birthday_greeted_on->toDateString());

        // Running again the same day is a no-op.
        $this->artisan('loyalty:occasion-offers')->assertSuccessful();
        $this->assertSame(150, $contact->fresh()->loyalty_points);
    }

    public function test_birthday_bonus_skipped_when_not_configured(): void
    {
        $tenant  = $this->enableLoyalty($this->setUpTenant()); // birthday_bonus_points = 0
        $contact = $this->contact($tenant);
        $contact->update(['birthday' => now()->subYears(20)->toDateString()]);

        $this->artisan('loyalty:occasion-offers')->assertSuccessful();

        $this->assertSame(0, $contact->fresh()->loyalty_points);
    }

    public function test_win_back_lists_only_lapsed_members(): void
    {
        $tenant = $this->enableLoyalty($this->setUpTenant(), ['inactive_days' => 30]);
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $lapsed = $this->contact($tenant);
        $this->givePoints($lapsed, 200);
        $this->invoice($tenant, $lapsed, 500, 'paid')->update(['paid_at' => now()->subDays(60)]);

        $active = Contact::create(['tenant_id' => $tenant->id, 'name' => 'Active Ann', 'phone' => '9111111111']);
        $this->givePoints($active, 200);
        $this->invoice($tenant, $active, 500, 'paid')->update(['paid_at' => now()->subDays(5)]);

        $this->actingAs($admin)->get(route('tenant.loyalty.win-back'))
            ->assertOk()
            ->assertSee('Repeat Customer')
            ->assertDontSee('Active Ann');
    }

    public function test_win_back_digest_notifies_admins_when_opted_in(): void
    {
        $tenant = $this->enableLoyalty($this->setUpTenant(), ['inactive_days' => 30, 'winback_digest' => true]);
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $c = $this->contact($tenant);
        $this->givePoints($c, 200);
        $this->invoice($tenant, $c, 500, 'paid')->update(['paid_at' => now()->subDays(90)]);

        $this->artisan('loyalty:win-back-digest')->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'type'    => 'loyalty.winback',
        ]);
    }

    // ── Phase 4: proactive notifications + WhatsApp self-check ──

    public function test_customer_is_emailed_when_notify_customers_is_on(): void
    {
        $tenant  = $this->enableLoyalty($this->setUpTenant(), ['notify_customers' => true]);
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = Contact::create([
            'tenant_id' => $tenant->id, 'name' => 'Emailed Ed', 'phone' => '9876500000', 'email' => 'ed@example.test',
        ]);

        $this->payInFull($admin, $this->invoice($tenant, $contact, 1500)); // 15 points

        $this->assertDatabaseHas('email_logs', [
            'contact_id' => $contact->id,
            'subject'    => "You earned loyalty points at {$tenant->name}",
        ]);
    }

    public function test_customer_is_not_messaged_when_opted_out(): void
    {
        $tenant  = $this->enableLoyalty($this->setUpTenant()); // notify_customers = false
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = Contact::create([
            'tenant_id' => $tenant->id, 'name' => 'Quiet Q', 'phone' => '9876500001', 'email' => 'q@example.test',
        ]);

        $this->payInFull($admin, $this->invoice($tenant, $contact, 1500));

        $this->assertDatabaseMissing('email_logs', ['contact_id' => $contact->id]);
    }

    private function waSetting(Tenant $tenant): WhatsappSetting
    {
        return WhatsappSetting::create([
            'tenant_id'       => $tenant->id,
            'phone_number_id' => 'PNID123',
            'access_token'    => 'tok_test',
            'is_connected'    => true,
        ]);
    }

    public function test_whatsapp_self_check_replies_with_balance(): void
    {
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.1']]], 200)]);

        $tenant  = $this->enableLoyalty($this->setUpTenant(), ['whatsapp_self_check' => true]);
        $contact = $this->contact($tenant); // phone 9876543210
        $this->givePoints($contact, 500);

        $handled = (new WhatsappChatbotService($this->waSetting($tenant)))
            ->handleLoyaltyKeyword('919876543210', 'points');

        $this->assertTrue($handled);
        Http::assertSent(fn ($req) => str_contains($req['text']['body'] ?? '', '500')
            && str_contains($req['text']['body'], 'Repeat Customer'));
    }

    public function test_whatsapp_self_check_is_off_by_default(): void
    {
        Http::fake();
        $tenant  = $this->enableLoyalty($this->setUpTenant()); // whatsapp_self_check = false
        $this->givePoints($this->contact($tenant), 500);

        $handled = (new WhatsappChatbotService($this->waSetting($tenant)))
            ->handleLoyaltyKeyword('919876543210', 'points');

        $this->assertFalse($handled);
        Http::assertNothingSent();
    }

    public function test_whatsapp_welcome_enrols_a_new_number(): void
    {
        Http::fake(['*' => Http::response(['messages' => [['id' => 'x']]], 200)]);
        $tenant = $this->enableLoyalty($this->setUpTenant(), ['welcome_bonus_points' => 100]);

        $handled = (new WhatsappChatbotService($this->waSetting($tenant)))
            ->handleLoyaltyWelcome('919812345678', 'JOIN', 'Ravi Sharma');

        $this->assertTrue($handled);
        $contact = Contact::where('tenant_id', $tenant->id)->where('phone', '919812345678')->first();
        $this->assertNotNull($contact);
        $this->assertSame('Ravi Sharma', $contact->name);
        $this->assertSame(100, $contact->loyalty_points);
        Http::assertSent(fn ($r) => str_contains($r['text']['body'] ?? '', '100'));
    }

    public function test_whatsapp_welcome_does_not_regift_an_existing_customer(): void
    {
        Http::fake(['*' => Http::response(['messages' => [['id' => 'x']]], 200)]);
        $tenant  = $this->enableLoyalty($this->setUpTenant(), ['welcome_bonus_points' => 100]);
        $contact = $this->contact($tenant); // phone 9876543210
        $this->givePoints($contact, 40);

        $handled = (new WhatsappChatbotService($this->waSetting($tenant)))
            ->handleLoyaltyWelcome('919876543210', 'JOIN', 'Repeat Customer');

        $this->assertTrue($handled);
        $this->assertSame(1, Contact::where('tenant_id', $tenant->id)->count());
        $this->assertSame(40, $contact->fresh()->loyalty_points); // unchanged
        Http::assertSent(fn ($r) => str_contains($r['text']['body'] ?? '', 'already a'));
    }

    public function test_whatsapp_welcome_is_off_by_default(): void
    {
        Http::fake();
        $tenant = $this->enableLoyalty($this->setUpTenant()); // welcome_bonus_points = 0

        $handled = (new WhatsappChatbotService($this->waSetting($tenant)))
            ->handleLoyaltyWelcome('919812345678', 'JOIN', 'Nobody');

        $this->assertFalse($handled);
        Http::assertNothingSent();
        $this->assertSame(0, Contact::where('tenant_id', $tenant->id)->count());
    }

    public function test_incoming_message_runs_loyalty_builtin_before_the_chatbot_guard(): void
    {
        Http::fake(['*' => Http::response(['messages' => [['id' => 'x']]], 200)]);
        $tenant  = $this->enableLoyalty($this->setUpTenant(), ['whatsapp_self_check' => true]);
        $contact = $this->contact($tenant);
        $this->givePoints($contact, 275);

        $wa = $this->waSetting($tenant);
        $wa->update(['chatbot_enabled' => false]); // full chatbot OFF

        $handled = (new WhatsappChatbotService($wa))->handleIncomingMessage('919876543210', 'points', null);

        $this->assertTrue($handled);
        Http::assertSent(fn ($r) => str_contains($r['text']['body'] ?? '', '275'));
    }

    public function test_chatbot_flow_fills_loyalty_placeholders(): void
    {
        Http::fake(['*' => Http::response(['messages' => [['id' => 'x']]], 200)]);
        $tenant  = $this->enableLoyalty($this->setUpTenant());
        $contact = $this->contact($tenant);
        $contact->forceFill(['loyalty_tier' => 'gold', 'loyalty_lifetime_points' => 12000])->save();
        $this->givePoints($contact, 500);

        $wa = $this->waSetting($tenant);
        $wa->update(['chatbot_enabled' => true]);
        \App\Models\WhatsappChatbotFlow::create([
            'tenant_id' => $tenant->id, 'name' => 'My Info', 'trigger_keywords' => ['myinfo'],
            'keyword_match' => 'exact', 'response_message' => 'Hi {{contact_name}}, you have {{loyalty_points}} points ({{loyalty_tier}}).',
            'is_active' => true,
        ]);

        (new WhatsappChatbotService($wa))->handleIncomingMessage('919876543210', 'myinfo', null);

        Http::assertSent(fn ($r) => str_contains($r['text']['body'] ?? '', 'you have 500 points (Gold)')
            && str_contains($r['text']['body'], 'Repeat Customer'));
    }

    public function test_chatbot_flow_action_enrols_the_sender(): void
    {
        Http::fake(['*' => Http::response(['messages' => [['id' => 'x']]], 200)]);
        $tenant = $this->enableLoyalty($this->setUpTenant(), ['welcome_bonus_points' => 50]);

        $wa = $this->waSetting($tenant);
        $wa->update(['chatbot_enabled' => true]);
        \App\Models\WhatsappChatbotFlow::create([
            'tenant_id' => $tenant->id, 'name' => 'Greeting', 'trigger_keywords' => ['namaste'],
            'keyword_match' => 'exact', 'response_message' => 'Welcome {{contact_name}}!',
            'action' => 'loyalty_join', 'is_active' => true,
        ]);

        (new WhatsappChatbotService($wa))->handleIncomingMessage('919811112222', 'namaste', 'Priya');

        $contact = Contact::where('tenant_id', $tenant->id)->where('phone', '919811112222')->first();
        $this->assertNotNull($contact);
        $this->assertSame(50, $contact->loyalty_points);
    }

    public function test_whatsapp_self_check_handles_unknown_number(): void
    {
        Http::fake(['*' => Http::response(['messages' => [['id' => 'x']]], 200)]);

        $tenant = $this->enableLoyalty($this->setUpTenant(), ['whatsapp_self_check' => true]);

        $handled = (new WhatsappChatbotService($this->waSetting($tenant)))
            ->handleLoyaltyKeyword('910000000000', 'balance');

        $this->assertTrue($handled);
        Http::assertSent(fn ($req) => str_contains($req['text']['body'] ?? '', "couldn't find a loyalty account"));
    }

    // ── Phase 5: public "check my rewards" page + OTP + API ─────

    private function publicToken(Tenant $tenant): string
    {
        $settings = $tenant->settings ?? [];
        $settings['loyalty']['public_lookup'] = true;
        $settings['rewards_token'] = 'rwd_' . \Illuminate\Support\Str::random(20);
        $tenant->update(['settings' => $settings]);

        return $settings['rewards_token'];
    }

    public function test_rewards_page_is_404_unless_opted_in(): void
    {
        $tenant = $this->enableLoyalty($this->setUpTenant());
        $token  = $this->publicToken($tenant);

        $this->get(route('public.rewards.show', $token))->assertOk();

        // Turn it off — the same link now 404s.
        $s = $tenant->fresh()->settings;
        $s['loyalty']['public_lookup'] = false;
        $tenant->update(['settings' => $s]);

        $this->get(route('public.rewards.show', $token))->assertNotFound();
    }

    public function test_request_otp_creates_a_code_without_leaking_existence(): void
    {
        $tenant  = $this->enableLoyalty($this->setUpTenant());
        $token   = $this->publicToken($tenant);
        $contact = Contact::create(['tenant_id' => $tenant->id, 'name' => 'Otp Olive', 'phone' => '9765012345', 'email' => 'olive@example.test']);

        $this->post(route('public.rewards.request-otp', $token), ['identifier' => 'olive@example.test'])
            ->assertOk()->assertSee('code is on its way');
        $this->assertDatabaseHas('loyalty_otps', ['contact_id' => $contact->id]);

        // Unknown identifier — same response, no code row.
        $this->post(route('public.rewards.request-otp', $token), ['identifier' => 'nobody@nowhere.test'])
            ->assertOk()->assertSee('code is on its way');
        $this->assertDatabaseCount('loyalty_otps', 1);
    }

    public function test_correct_code_reveals_points_wrong_code_does_not(): void
    {
        $tenant  = $this->enableLoyalty($this->setUpTenant());
        $token   = $this->publicToken($tenant);
        $contact = Contact::create(['tenant_id' => $tenant->id, 'name' => 'Verified Val', 'phone' => '9765099999', 'email' => 'val@example.test']);
        $this->givePoints($contact, 275);

        [$otp, $code] = LoyaltyOtp::issue($tenant->id, $contact, 'val@example.test', 'email', null);

        // Wrong code first.
        $this->post(route('public.rewards.verify', $token), ['otp_id' => $otp->id, 'code' => '000000'])
            ->assertRedirect();
        $this->assertSame(1, $otp->fresh()->attempts);

        // Correct code → session set → result page shows the balance.
        $this->post(route('public.rewards.verify', $token), ['otp_id' => $otp->id, 'code' => $code])
            ->assertRedirect(route('public.rewards.show', $token));

        $this->get(route('public.rewards.show', $token))
            ->assertOk()
            ->assertSee('Verified Val')
            ->assertSee('275');
    }

    public function test_otp_locks_after_five_bad_attempts(): void
    {
        $tenant  = $this->enableLoyalty($this->setUpTenant());
        $contact = $this->contact($tenant);
        [$otp, $code] = LoyaltyOtp::issue($tenant->id, $contact, '9876543210', 'whatsapp', null);

        for ($i = 0; $i < 5; $i++) {
            $this->assertFalse($otp->attempt('999999'));
        }

        $this->assertTrue($otp->fresh()->isLocked());
        $this->assertFalse($otp->fresh()->attempt($code)); // even the right code is refused now
    }

    public function test_api_lookup_returns_a_snapshot(): void
    {
        $tenant  = $this->enableLoyalty($this->setUpTenant());
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = $this->contact($tenant);
        $this->givePoints($contact, 640);

        $this->giveActiveSubscription($tenant);
        $key = ApiKey::generate($tenant->id, $admin->id, 'test')->key;

        $this->withHeader('X-API-Key', $key)
            ->getJson('/api/v1/tenant/loyalty/lookup?identifier=9876543210')
            ->assertOk()
            ->assertJsonPath('data.points', 640)
            ->assertJsonPath('data.name', 'Repeat Customer');
    }

    public function test_api_lookup_404_for_unknown_and_403_when_module_off(): void
    {
        $tenant = $this->enableLoyalty($this->setUpTenant());
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $this->giveActiveSubscription($tenant);
        $key    = ApiKey::generate($tenant->id, $admin->id, 'test')->key;

        $this->withHeader('X-API-Key', $key)
            ->getJson('/api/v1/tenant/loyalty/lookup?identifier=0000000000')
            ->assertNotFound();

        $s = $tenant->fresh()->settings;
        $s['modules']['loyalty'] = false;
        $tenant->update(['settings' => $s]);

        $this->withHeader('X-API-Key', $key)
            ->getJson('/api/v1/tenant/loyalty/lookup?identifier=9876543210')
            ->assertForbidden();
    }

    // ── Phase 6: targeted campaigns / customer coupons ─────────

    private function draftCampaign(Tenant $tenant, array $overrides = []): LoyaltyCampaign
    {
        return LoyaltyCampaign::create(array_merge([
            'tenant_id'                => $tenant->id,
            'name'                     => 'Test Campaign',
            'segment_type'             => 'spend',
            'segment_config'           => ['min_spend' => 5000, 'within_days' => null],
            'reward_type'              => 'percent',
            'reward_value'             => 10,
            'code_mode'                => 'unique',
            'usage_limit_per_customer' => 1,
            'delivery'                 => 'none',
            'status'                   => 'draft',
        ], $overrides));
    }

    public function test_spend_segment_preview_and_launch_issue_codes(): void
    {
        $tenant = $this->enableLoyalty($this->setUpTenant());
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $big   = Contact::create(['tenant_id' => $tenant->id, 'name' => 'Big Spender', 'phone' => '9700000001']);
        $small = Contact::create(['tenant_id' => $tenant->id, 'name' => 'Small Spender', 'phone' => '9700000002']);
        $this->invoice($tenant, $big, 6000, 'paid');
        $this->invoice($tenant, $small, 1000, 'paid');

        $campaign = $this->draftCampaign($tenant);

        $this->assertSame(1, app(LoyaltyCampaignService::class)->previewCount($campaign));

        $this->actingAs($admin)->post(route('tenant.loyalty.campaigns.launch', $campaign->id))->assertRedirect();

        $campaign->refresh();
        $this->assertSame('active', $campaign->status);
        $this->assertSame(1, $campaign->recipients()->count());
        $this->assertSame($big->id, $campaign->recipients()->first()->contact_id);
    }

    public function test_points_campaign_pays_out_on_launch(): void
    {
        $tenant  = $this->enableLoyalty($this->setUpTenant());
        $contact = $this->contact($tenant);
        $contact->forceFill(['loyalty_tier' => 'gold', 'loyalty_lifetime_points' => 12000, 'loyalty_points' => 12000])->save();

        $campaign = $this->draftCampaign($tenant, [
            'segment_type'   => 'tier',
            'segment_config' => ['tier' => 'gold'],
            'reward_type'    => 'points',
            'reward_value'   => 200,
        ]);

        app(LoyaltyCampaignService::class)->launch($campaign);

        $this->assertSame(12200, $contact->fresh()->loyalty_points);
        $this->assertNotNull($campaign->recipients()->first()->redeemed_at);
    }

    public function test_coupon_applies_to_invoice_and_can_be_removed(): void
    {
        $tenant  = $this->enableLoyalty($this->setUpTenant());
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = $this->contact($tenant);
        $this->invoice($tenant, $contact, 6000, 'paid'); // qualifies for the spend segment

        $campaign = $this->draftCampaign($tenant, ['reward_value' => 10]); // 10% off
        app(LoyaltyCampaignService::class)->launch($campaign);
        $code = $campaign->recipients()->first()->code;

        $bill = $this->invoice($tenant, $contact, 2000);

        $this->actingAs($admin)->post(route('tenant.invoices.apply_coupon', $bill->id), ['code' => $code])->assertRedirect();

        $bill->refresh();
        $this->assertEqualsWithDelta(200.0, (float) $bill->campaign_discount, 0.01); // 10% of 2000
        $this->assertEqualsWithDelta(1800.0, $bill->due_amount, 0.01);
        $this->assertSame(1, $campaign->fresh()->redeemed_count);

        $this->actingAs($admin)->post(route('tenant.invoices.remove_coupon', $bill->id))->assertRedirect();

        $bill->refresh();
        $this->assertEqualsWithDelta(0.0, (float) $bill->campaign_discount, 0.01);
        $this->assertSame(0, $campaign->fresh()->redeemed_count);
    }

    public function test_coupon_rejected_for_a_different_customer_and_after_its_use_limit(): void
    {
        $tenant = $this->enableLoyalty($this->setUpTenant());
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $a = Contact::create(['tenant_id' => $tenant->id, 'name' => 'Cust A', 'phone' => '9700000010']);
        $b = Contact::create(['tenant_id' => $tenant->id, 'name' => 'Cust B', 'phone' => '9700000011']);
        $this->invoice($tenant, $a, 6000, 'paid');

        $campaign = $this->draftCampaign($tenant, ['reward_type' => 'flat', 'reward_value' => 100]);
        app(LoyaltyCampaignService::class)->launch($campaign);
        $code = $campaign->recipients()->first()->code;

        // Wrong customer.
        $wrongBill = $this->invoice($tenant, $b, 1000);
        $this->actingAs($admin)->post(route('tenant.invoices.apply_coupon', $wrongBill->id), ['code' => $code])
            ->assertRedirect();
        $this->assertEqualsWithDelta(0.0, (float) $wrongBill->fresh()->campaign_discount, 0.01);

        // Right customer, first use OK.
        $bill1 = $this->invoice($tenant, $a, 1000);
        $this->actingAs($admin)->post(route('tenant.invoices.apply_coupon', $bill1->id), ['code' => $code])->assertRedirect();
        $this->assertEqualsWithDelta(100.0, (float) $bill1->fresh()->campaign_discount, 0.01);

        // Second use on another bill — over the per-customer limit.
        $bill2 = $this->invoice($tenant, $a, 1000);
        $this->actingAs($admin)->post(route('tenant.invoices.apply_coupon', $bill2->id), ['code' => $code])->assertRedirect();
        $this->assertEqualsWithDelta(0.0, (float) $bill2->fresh()->campaign_discount, 0.01);
    }

    public function test_campaign_area_needs_loyalty_manage(): void
    {
        $tenant = $this->enableLoyalty($this->setUpTenant());
        $staff  = $this->makeUser($tenant, 'staff');

        $this->actingAs($staff)->get(route('tenant.loyalty.campaigns.index'))->assertForbidden();
    }

    private function paidInvoiceWithItem(Tenant $tenant, Contact $contact, int $productId, float $qty, float $rate): Invoice
    {
        $total = $qty * $rate;

        return Invoice::create([
            'tenant_id'   => $tenant->id,
            'contact_id'  => $contact->id,
            'number'      => Invoice::generateNumber($tenant->id),
            'date'        => now()->toDateString(),
            'due_date'    => now()->addDays(7)->toDateString(),
            'items'       => [['product_id' => $productId, 'description' => 'Item', 'quantity' => $qty, 'rate' => $rate]],
            'subtotal'    => $total,
            'total'       => $total,
            'paid_amount' => $total,
            'status'      => 'paid',
            'paid_at'     => now(),
        ]);
    }

    public function test_category_segment_matches_by_line_item_spend(): void
    {
        $tenant = $this->enableLoyalty($this->setUpTenant());

        $latte = \App\Models\Product::create(['tenant_id' => $tenant->id, 'name' => 'Latte', 'rate' => 200, 'tax_percent' => 5, 'category' => 'Beverages']);
        $chair = \App\Models\Product::create(['tenant_id' => $tenant->id, 'name' => 'Chair', 'rate' => 3000, 'tax_percent' => 18, 'category' => 'Furniture']);

        $beverageFan = Contact::create(['tenant_id' => $tenant->id, 'name' => 'Bev Fan', 'phone' => '9600000001']);
        $furnitureFan = Contact::create(['tenant_id' => $tenant->id, 'name' => 'Furn Fan', 'phone' => '9600000002']);

        $this->paidInvoiceWithItem($tenant, $beverageFan, $latte->id, 5, 200);   // ₹1000 beverages
        $this->paidInvoiceWithItem($tenant, $furnitureFan, $chair->id, 1, 3000);  // furniture only

        $svc = app(LoyaltyCampaignService::class);

        $any = $this->draftCampaign($tenant, [
            'segment_type'   => 'category',
            'segment_config' => ['category' => 'Beverages', 'min_spend' => 0, 'within_days' => null],
        ]);
        $this->assertSame(1, $svc->previewCount($any));

        $bigSpender = $this->draftCampaign($tenant, [
            'segment_type'   => 'category',
            'segment_config' => ['category' => 'Beverages', 'min_spend' => 2000, 'within_days' => null],
        ]);
        $this->assertSame(0, $svc->previewCount($bigSpender)); // spent only ₹1000

        $svc->launch($any);
        $this->assertSame($beverageFan->id, $any->fresh()->recipients()->first()->contact_id);
    }

    public function test_product_and_service_accept_a_category(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $this->actingAs($admin)->post(route('tenant.products.store'), [
            'name' => 'Espresso', 'rate' => 150, 'tax_percent' => 5, 'category' => 'Beverages',
        ])->assertRedirect();
        $this->assertSame('Beverages', \App\Models\Product::where('name', 'Espresso')->first()->category);

        $svc = \App\Models\Service::create([
            'tenant_id' => $tenant->id, 'name' => 'Deep Clean', 'rate' => 500, 'tax_percent' => 18, 'category' => 'Repairs',
        ]);
        $this->assertSame('Repairs', $svc->fresh()->category);
    }

    // ── §7 extras: multiplier / expiry nudge / reward catalog / top customers ──

    public function test_flash_day_doubles_points_earned(): void
    {
        $today  = strtolower(now()->format('D'));
        $tenant = $this->enableLoyalty($this->setUpTenant(), ['multiplier' => 2, 'multiplier_days' => [$today]]);
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $contact = $this->contact($tenant);

        $this->payInFull($admin, $this->invoice($tenant, $contact, 1000)); // 10 pts base → 20 on a flash day

        $this->assertSame(20, $contact->fresh()->loyalty_points);
    }

    public function test_expiry_reminder_fires_once_then_guards(): void
    {
        $tenant  = $this->enableLoyalty($this->setUpTenant(), ['expiry_reminder_days' => 7]);
        $contact = Contact::create(['tenant_id' => $tenant->id, 'name' => 'Expiry Ed', 'phone' => '9550000001', 'email' => 'ed@ex.test']);
        $lot = app(LoyaltyService::class)->manualAdjust($contact, 200, 'seed');
        $lot->update(['earn_expires_at' => now()->addDays(5)]);

        $this->assertSame(1, app(LoyaltyService::class)->sendExpiryReminders($tenant));
        $this->assertNotNull($lot->fresh()->expiry_reminded_at);
        $this->assertDatabaseHas('email_logs', ['contact_id' => $contact->id, 'subject' => "Your {$tenant->name} points expire soon"]);

        $this->assertSame(0, app(LoyaltyService::class)->sendExpiryReminders($tenant)); // already reminded
    }

    public function test_catalog_reward_redeem_and_remove(): void
    {
        $tenant  = $this->enableLoyalty($this->setUpTenant(), ['reward_catalog' => [['name' => 'Free Coffee', 'points' => 500]]]);
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = $this->contact($tenant);
        $this->givePoints($contact, 600);
        $bill = $this->invoice($tenant, $contact, 2000);

        $this->actingAs($admin)->post(route('tenant.invoices.redeem_reward', $bill->id), ['reward' => 'Free Coffee'])->assertRedirect();

        $bill->refresh();
        $this->assertSame('Free Coffee', $bill->loyalty_reward);
        $this->assertSame(500, $bill->loyalty_reward_points);
        $this->assertSame(100, $contact->fresh()->loyalty_points);
        $this->assertEqualsWithDelta(2000.0, $bill->due_amount, 0.01); // reward doesn't reduce cash

        $this->actingAs($admin)->post(route('tenant.invoices.remove_reward', $bill->id))->assertRedirect();
        $this->assertSame(600, $contact->fresh()->loyalty_points);
        $this->assertNull($bill->fresh()->loyalty_reward);
    }

    public function test_catalog_reward_rejected_without_enough_points(): void
    {
        $tenant  = $this->enableLoyalty($this->setUpTenant(), ['reward_catalog' => [['name' => 'Free Meal', 'points' => 1000]]]);
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = $this->contact($tenant);
        $this->givePoints($contact, 300);
        $bill = $this->invoice($tenant, $contact, 500);

        $this->actingAs($admin)->post(route('tenant.invoices.redeem_reward', $bill->id), ['reward' => 'Free Meal'])
            ->assertRedirect();

        $this->assertSame(0, $bill->fresh()->loyalty_reward_points);
        $this->assertSame(300, $contact->fresh()->loyalty_points);
    }

    public function test_top_customers_ranks_by_spend(): void
    {
        $tenant = $this->enableLoyalty($this->setUpTenant());
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $whale  = Contact::create(['tenant_id' => $tenant->id, 'name' => 'Whale Wilma', 'phone' => '9540000001']);
        $minnow = Contact::create(['tenant_id' => $tenant->id, 'name' => 'Minnow Max', 'phone' => '9540000002']);
        $this->invoice($tenant, $whale, 9000, 'paid');
        $this->invoice($tenant, $minnow, 500, 'paid');

        $this->actingAs($admin)->get(route('tenant.loyalty.top-customers', ['period' => 'all']))
            ->assertOk()
            ->assertSeeInOrder(['Whale Wilma', 'Minnow Max']);
    }
}
