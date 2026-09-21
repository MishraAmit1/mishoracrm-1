<?php

namespace Tests\Feature\Portal;

use App\Models\Contact;
use App\Models\Customer;
use App\Models\LoyaltyCampaign;
use App\Models\LoyaltyCampaignRecipient;
use App\Models\Tenant;
use App\Services\LoyaltyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function shop(string $name = 'Salon', bool $loyalty = true, bool $portal = true, array $rules = []): Tenant
    {
        $tenant = $this->setUpTenant();
        $tenant->update(['name' => $name]);

        return $this->configure($tenant, $loyalty, $portal, $rules);
    }

    private function configure(Tenant $tenant, bool $loyalty = true, bool $portal = true, array $rules = []): Tenant
    {
        $settings = $tenant->settings ?? [];
        $settings['modules']['loyalty']         = $loyalty;
        $settings['modules']['customer_portal'] = $portal;
        if ($rules) {
            $settings['loyalty'] = array_replace(Tenant::LOYALTY_DEFAULTS, $rules);
        }
        $tenant->update(['settings' => $settings]);

        return $tenant->fresh();
    }

    private function customer(string $phone = '9876543210'): Customer
    {
        return Customer::create(['phone' => $phone]);
    }

    // A verified contact for the customer at a tenant, with a points balance.
    private function linkedContact(Tenant $tenant, Customer $customer, int $points = 120, bool $verified = true): Contact
    {
        $contact = Contact::create(['tenant_id' => $tenant->id, 'name' => 'Ravi', 'phone' => $customer->phone]);
        $contact->forceFill(['customer_id' => $customer->id, 'phone_verified' => $verified])->save();

        if ($points > 0) {
            app(LoyaltyService::class)->manualAdjust($contact->fresh(), $points, 'seed');
        }

        return $contact->fresh();
    }

    // Signs the customer in on the customer guard ONLY. actingAs($c, 'customer')
    // would also make it the default guard, which never happens in production
    // and changes how tenant scoping / audit logging behave.
    private function as(Customer $customer): static
    {
        $this->app['auth']->guard('customer')->setUser($customer);

        return $this;
    }

    private function campaign(Tenant $tenant, array $overrides = []): LoyaltyCampaign
    {
        return LoyaltyCampaign::create(array_merge([
            'tenant_id'                => $tenant->id,
            'name'                     => 'Diwali Special',
            'segment_type'             => 'manual',
            'segment_config'           => [],
            'reward_type'              => 'percent',
            'reward_value'             => 10,
            'code_mode'                => 'unique',
            'usage_limit_per_customer' => 1,
            'delivery'                 => 'none',
            'status'                   => 'active',
        ], $overrides));
    }

    private function recipient(LoyaltyCampaign $campaign, Contact $contact, array $overrides = []): LoyaltyCampaignRecipient
    {
        return LoyaltyCampaignRecipient::create(array_merge([
            'tenant_id'           => $campaign->tenant_id,
            'loyalty_campaign_id' => $campaign->id,
            'contact_id'          => $contact->id,
            'code'                => 'K7P2QF',
        ], $overrides));
    }

    // ── Access ─────────────────────────────────────────────────

    public function test_wallet_requires_a_customer_login(): void
    {
        $this->get('/wallet')->assertRedirect(route('portal.login'));
    }

    public function test_a_staff_login_does_not_grant_wallet_access(): void
    {
        $tenant = $this->shop();
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $this->actingAs($admin)->get('/wallet')->assertRedirect(route('portal.login'));
    }

    public function test_a_customer_request_never_makes_the_customer_the_default_guard(): void
    {
        $customer = $this->customer();
        $tenant   = $this->shop();
        $contact  = $this->linkedContact($tenant, $customer, 400, false);
        $this->makeUser($tenant, 'tenant_admin');

        // Tenant scoping and audit logging read auth()->user() and assume a tenant
        // User. If the portal flipped the default guard they'd get a Customer.
        $this->as($customer)->get('/wallet')->assertOk();
        $this->assertSame('web', $this->app['auth']->getDefaultDriver());

        $this->as($customer)
            ->post(route('portal.wallet.confirm', $tenant->id), ['contact_id' => $contact->id, 'answer' => 'yes'])
            ->assertRedirect();
        $this->assertSame('web', $this->app['auth']->getDefaultDriver());

        // ...and no audit row got a customer id stored as its acting user.
        $this->assertDatabaseMissing('audit_logs', ['model_type' => Contact::class, 'action' => 'updated', 'user_id' => $customer->id]);
    }

    public function test_blocked_customer_is_locked_out_of_the_wallet(): void
    {
        $customer = $this->customer();
        $customer->forceFill(['blocked_at' => now()])->save();

        $this->as($customer)->get('/wallet')->assertForbidden();
    }

    // ── Card list ──────────────────────────────────────────────

    public function test_empty_wallet_shows_a_friendly_empty_state(): void
    {
        $this->as($this->customer())->get('/wallet')
            ->assertOk()
            ->assertSee('No rewards cards yet');
    }

    public function test_card_list_only_shows_verified_shops_with_both_modules_on(): void
    {
        $customer = $this->customer();

        $good        = $this->shop('Good Salon');
        $portalOff   = $this->shop('Portal Off Cafe', true, false);
        $loyaltyOff  = $this->shop('Loyalty Off Bakery', false, true);
        $unverified  = $this->shop('Unverified Gym');

        $this->linkedContact($good, $customer);
        $this->linkedContact($portalOff, $customer);
        $this->linkedContact($loyaltyOff, $customer);
        $this->linkedContact($unverified, $customer, 120, false);

        $this->as($customer)->get('/wallet')
            ->assertOk()
            ->assertSee('Good Salon')
            ->assertDontSee('Portal Off Cafe')
            ->assertDontSee('Loyalty Off Bakery');
    }

    public function test_a_shop_turning_the_portal_on_later_appears_without_relinking(): void
    {
        $customer = $this->customer();
        $tenant   = $this->shop('Late Shop', true, false);
        $this->linkedContact($tenant, $customer);

        $this->as($customer)->get('/wallet')->assertDontSee('Late Shop');

        $this->configure($tenant, true, true);

        $this->as($customer)->get('/wallet')->assertSee('Late Shop');
    }

    public function test_a_shop_with_nothing_to_show_yet_is_left_off_the_list(): void
    {
        $customer = $this->customer();
        $tenant   = $this->shop('Brand New Shop');
        $this->linkedContact($tenant, $customer, 0);

        $this->as($customer)->get('/wallet')->assertDontSee('Brand New Shop')->assertSee('No rewards cards yet');
    }

    public function test_points_card_shows_points_and_redeemable_value(): void
    {
        $customer = $this->customer();
        $tenant   = $this->shop('Points Shop', true, true, ['redeem_points_block' => 100, 'redeem_value' => 10]);
        $this->linkedContact($tenant, $customer, 340);

        $this->as($customer)->get('/wallet')
            ->assertSee('Points Shop')
            ->assertSee('340')
            ->assertSee('points available')
            ->assertSee('₹30');
    }

    public function test_stamp_shop_card_shows_a_stamp_card_not_a_broken_points_card(): void
    {
        $customer = $this->customer();
        $tenant   = $this->shop('Kirana', true, true, ['mode' => 'stamps', 'stamps_required' => 10, 'stamp_reward' => 'Free 1kg rice']);
        $contact  = $this->linkedContact($tenant, $customer, 1); // has a balance so the card is listed

        $response = $this->as($customer)->get('/wallet');

        $response->assertSee('Kirana')
            ->assertSee('0/10 stamps')
            ->assertSee('Free 1kg rice')
            ->assertDontSee('points available');
        $this->assertSame(10, substr_count($response->getContent(), 'class="wc-stamp '));
    }

    public function test_stamp_card_shows_real_progress_unlocked_rewards_and_stamp_history(): void
    {
        $customer = $this->customer();
        $tenant   = $this->shop('Kirana', true, true, ['mode' => 'stamps', 'stamps_required' => 5, 'stamp_reward' => 'Free coffee']);
        $contact  = $this->linkedContact($tenant, $customer, 0);
        $contact->forceFill(['customer_id' => $customer->id, 'phone_verified' => true])->save();

        app(LoyaltyService::class)->awardStamp($contact->fresh(), null, 'Counter');
        $contact->forceFill(['stamp_count' => 3, 'stamp_rewards_earned' => 1])->save();

        $page = $this->as($customer)->get(route('portal.wallet.show', $tenant->id));

        $page->assertOk()
            ->assertSee('3/5 stamps')
            ->assertSee('2 more for Free coffee')
            ->assertSee('1 free reward unlocked')
            ->assertSee('Counter stamp')
            ->assertSee('+1 stamp');
        $this->assertSame(3, substr_count($page->getContent(), 'wc-stamp on'));

        // ...and the list view (the card qualifies on stamps alone).
        $this->as($customer)->get('/wallet')->assertSee('Kirana')->assertSee('3/5 stamps');
    }

    public function test_tenant_contact_panel_shows_the_stamp_card_for_stamp_shops(): void
    {
        $tenant  = $this->shop('Kirana', true, true, ['mode' => 'stamps', 'stamps_required' => 8]);
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = $this->linkedContact($tenant, $this->customer(), 0);
        $contact->forceFill(['stamp_count' => 4, 'stamp_rewards_earned' => 2])->save();

        $this->actingAs($admin)->get(route('tenant.contacts.show', $contact->id))
            ->assertOk()
            ->assertSee('Stamp card')
            ->assertSee('4/8')
            ->assertSee('2 rewards unclaimed');
    }

    public function test_both_mode_card_leads_with_stamps_and_keeps_points_compact(): void
    {
        $customer = $this->customer();
        $tenant   = $this->shop('Combo', true, true, ['mode' => 'both', 'stamps_required' => 5, 'stamp_reward' => 'Free coffee']);
        $this->linkedContact($tenant, $customer, 250);

        $this->as($customer)->get('/wallet')
            ->assertSee('0/5 stamps')
            ->assertSee('Free coffee')
            ->assertSee('250')
            ->assertDontSee('points available');
    }

    public function test_offer_count_badge_appears_on_the_card(): void
    {
        $customer = $this->customer();
        $tenant   = $this->shop('Offer Shop');
        $contact  = $this->linkedContact($tenant, $customer);
        $this->recipient($this->campaign($tenant), $contact);

        $this->as($customer)->get('/wallet')->assertSee('1 offer');
    }

    // ── One shop ───────────────────────────────────────────────

    public function test_shop_page_shows_that_shops_card_offers_and_history(): void
    {
        $customer = $this->customer();
        $tenant   = $this->shop('Detail Shop');
        $contact  = $this->linkedContact($tenant, $customer, 200);
        $this->recipient($this->campaign($tenant, ['name' => 'Festive 10']), $contact, ['code' => 'ABC123']);

        $this->as($customer)->get(route('portal.wallet.show', $tenant->id))
            ->assertOk()
            ->assertSee('Detail Shop')
            ->assertSee('200')
            ->assertSee('Festive 10')
            ->assertSee('ABC123')
            ->assertSee('10% off')
            ->assertSee('Recent activity')
            ->assertSee('seed');
    }

    public function test_shop_page_is_404_when_the_customer_is_not_linked_to_it(): void
    {
        $customer = $this->customer();
        $tenant   = $this->shop();

        $this->as($customer)->get(route('portal.wallet.show', $tenant->id))->assertNotFound();
    }

    public function test_shop_page_is_404_for_an_unconfirmed_link(): void
    {
        $customer = $this->customer();
        $tenant   = $this->shop();
        $this->linkedContact($tenant, $customer, 500, false);

        $this->as($customer)->get(route('portal.wallet.show', $tenant->id))->assertNotFound();
    }

    public function test_shop_page_is_404_when_the_portal_is_off_even_if_linked(): void
    {
        $customer = $this->customer();
        $tenant   = $this->shop('Off', true, false);
        $this->linkedContact($tenant, $customer);

        $this->as($customer)->get(route('portal.wallet.show', $tenant->id))->assertNotFound();
    }

    public function test_shop_page_is_404_when_loyalty_is_off_even_if_the_portal_is_on(): void
    {
        $customer = $this->customer();
        $tenant   = $this->shop('Off', false, true);
        $this->linkedContact($tenant, $customer);

        $this->as($customer)->get(route('portal.wallet.show', $tenant->id))->assertNotFound();
    }

    public function test_shop_page_is_404_for_an_unknown_shop(): void
    {
        $this->as($this->customer())->get(route('portal.wallet.show', 999999))->assertNotFound();
    }

    // ── Isolation ──────────────────────────────────────────────

    public function test_one_customer_never_sees_another_customers_card(): void
    {
        $tenant = $this->shop('Shared Shop');
        $ravi   = $this->customer('9876543210');
        $sunita = $this->customer('9123456789');

        $raviContact = $this->linkedContact($tenant, $ravi, 999);
        $this->recipient($this->campaign($tenant, ['name' => 'Ravi Only']), $raviContact);

        // Sunita has no contact here at all.
        $this->as($sunita)->get(route('portal.wallet.show', $tenant->id))->assertNotFound();
        $this->as($sunita)->get('/wallet')->assertDontSee('Shared Shop')->assertDontSee('999');
    }

    public function test_the_card_only_carries_the_data_of_the_shop_it_belongs_to(): void
    {
        $customer = $this->customer();
        $shopA    = $this->shop('Shop A');
        $shopB    = $this->shop('Shop B');

        $contactA = $this->linkedContact($shopA, $customer, 111);
        $contactB = $this->linkedContact($shopB, $customer, 777);
        $this->recipient($this->campaign($shopB, ['name' => 'B Secret Offer']), $contactB);

        $page = $this->as($customer)->get(route('portal.wallet.show', $shopA->id));

        $page->assertOk()->assertSee('Shop A')->assertSee('111');
        $page->assertDontSee('Shop B')->assertDontSee('777')->assertDontSee('B Secret Offer');
    }

    public function test_a_staff_member_of_the_same_browser_does_not_break_or_leak_the_wallet(): void
    {
        $customer = $this->customer();
        $mine     = $this->shop('My Shop');
        $other    = $this->shop('Other Shop');
        $this->linkedContact($mine, $customer, 50);
        $this->linkedContact($other, $customer, 60);

        // A logged-in staff user of one tenant must not change what the wallet shows.
        $staff = $this->makeUser($mine, 'tenant_admin');
        $this->actingAs($staff);
        $this->as($customer);

        $this->get('/wallet')->assertOk()->assertSee('My Shop')->assertSee('Other Shop');
        $this->get(route('portal.wallet.show', $other->id))->assertOk()->assertSee('Other Shop')->assertSee('60');
    }

    // ── Offers ─────────────────────────────────────────────────

    public function test_only_usable_offers_are_listed(): void
    {
        $customer = $this->customer();
        $tenant   = $this->shop('Offers Shop');
        $contact  = $this->linkedContact($tenant, $customer);

        $this->recipient($this->campaign($tenant, ['name' => 'Live Offer']), $contact, ['code' => 'LIVE01']);
        $this->recipient($this->campaign($tenant, ['name' => 'Expired Offer', 'expires_at' => now()->subDay()]), $contact, ['code' => 'OLD001']);
        $this->recipient($this->campaign($tenant, ['name' => 'Ended Offer', 'status' => 'ended']), $contact, ['code' => 'END001']);
        $this->recipient($this->campaign($tenant, ['name' => 'Capped Offer', 'total_redemption_cap' => 5, 'redeemed_count' => 5]), $contact, ['code' => 'CAP001']);
        $this->recipient($this->campaign($tenant, ['name' => 'Used Offer']), $contact, ['code' => 'USED01', 'redeemed_count' => 1]);

        $this->as($customer)->get(route('portal.wallet.show', $tenant->id))
            ->assertSee('Live Offer')->assertSee('LIVE01')
            ->assertDontSee('Expired Offer')
            ->assertDontSee('Ended Offer')
            ->assertDontSee('Capped Offer')
            ->assertDontSee('Used Offer');
    }

    // ── "Is this you?" ─────────────────────────────────────────

    public function test_history_contacts_wait_in_a_pending_section_until_confirmed(): void
    {
        $customer = $this->customer();
        $tenant   = $this->shop('Pending Shop');
        $this->linkedContact($tenant, $customer, 400, false);

        $this->as($customer)->get('/wallet')
            ->assertOk()
            ->assertSee('Is this you?')
            ->assertSee('Pending Shop')
            ->assertDontSee('No rewards cards yet');
    }

    public function test_confirming_yes_moves_the_shop_into_the_wallet(): void
    {
        $customer = $this->customer();
        $tenant   = $this->shop('Pending Shop');
        $contact  = $this->linkedContact($tenant, $customer, 400, false);

        $this->as($customer)
            ->post(route('portal.wallet.confirm', $tenant->id), ['contact_id' => $contact->id, 'answer' => 'yes'])
            ->assertRedirect(route('portal.wallet.index'));

        $this->assertTrue($contact->fresh()->phone_verified);
        $this->as($customer)->get(route('portal.wallet.show', $tenant->id))->assertOk()->assertSee('400');
    }

    public function test_confirming_not_me_unlinks_flags_and_warns_the_shop(): void
    {
        $customer = $this->customer();
        $tenant   = $this->shop('Pending Shop');
        $admin    = $this->makeUser($tenant, 'tenant_admin');
        $contact  = $this->linkedContact($tenant, $customer, 400, false);

        $this->as($customer)
            ->post(route('portal.wallet.confirm', $tenant->id), ['contact_id' => $contact->id, 'answer' => 'no'])
            ->assertRedirect(route('portal.wallet.index'));

        $fresh = $contact->fresh();
        $this->assertNull($fresh->customer_id);
        $this->assertNotNull($fresh->link_flagged_at);
        $this->assertDatabaseHas('notifications', ['user_id' => $admin->id, 'type' => 'loyalty.wrong_number']);

        $this->as($customer)->get(route('portal.wallet.show', $tenant->id))->assertNotFound();
    }

    public function test_cannot_confirm_someone_elses_contact(): void
    {
        $tenant   = $this->shop();
        $owner    = $this->customer('9876543210');
        $stranger = $this->customer('9123456789');
        $contact  = $this->linkedContact($tenant, $owner, 400, false);

        $this->as($stranger)
            ->post(route('portal.wallet.confirm', $tenant->id), ['contact_id' => $contact->id, 'answer' => 'yes'])
            ->assertNotFound();

        $this->assertFalse($contact->fresh()->phone_verified);
    }

    public function test_confirm_is_404_when_the_shop_has_the_portal_off(): void
    {
        $customer = $this->customer();
        $tenant   = $this->shop('Off', true, false);
        $contact  = $this->linkedContact($tenant, $customer, 400, false);

        $this->as($customer)
            ->post(route('portal.wallet.confirm', $tenant->id), ['contact_id' => $contact->id, 'answer' => 'yes'])
            ->assertNotFound();
    }

    // ── Profile ────────────────────────────────────────────────

    public function test_profile_page_renders_and_saves(): void
    {
        $customer = $this->customer();

        $this->as($customer)->get(route('portal.profile.edit'))->assertOk()->assertSee('My profile');

        $this->as($customer)
            ->post(route('portal.profile.update'), ['name' => 'Ravi Kumar', 'email' => 'Ravi@Example.com'])
            ->assertRedirect(route('portal.wallet.index'));

        $fresh = $customer->fresh();
        $this->assertSame('Ravi Kumar', $fresh->name);
        $this->assertSame('ravi@example.com', $fresh->email);
    }

    public function test_changing_the_email_clears_its_verification_and_never_touches_contacts(): void
    {
        $customer = $this->customer();
        $customer->forceFill(['email' => 'old@example.com', 'email_verified_at' => now()])->save();
        $tenant  = $this->shop();
        $contact = $this->linkedContact($tenant, $customer);
        $before  = $contact->fresh()->only(['name', 'email', 'phone']);

        $this->as($customer)->post(route('portal.profile.update'), ['name' => 'New Name', 'email' => 'new@example.com']);

        $this->assertNull($customer->fresh()->email_verified_at);
        $this->assertSame($before, $contact->fresh()->only(['name', 'email', 'phone']));
    }

    public function test_keeping_the_same_email_keeps_it_verified(): void
    {
        $customer = $this->customer();
        $customer->forceFill(['email' => 'same@example.com', 'email_verified_at' => now()])->save();

        $this->as($customer)->post(route('portal.profile.update'), ['name' => 'X', 'email' => 'SAME@example.com']);

        $this->assertNotNull($customer->fresh()->email_verified_at);
    }

    public function test_profile_rejects_a_bad_email(): void
    {
        $this->as($this->customer())
            ->post(route('portal.profile.update'), ['name' => 'X', 'email' => 'nope'])
            ->assertSessionHasErrors('email');
    }

    // ── Tenant side ────────────────────────────────────────────

    public function test_needs_review_lists_flagged_contacts_for_the_owning_tenant_only(): void
    {
        $tenant = $this->shop();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $other  = $this->shop('Other');

        $mine   = Contact::create(['tenant_id' => $tenant->id, 'name' => 'Flagged Fred', 'phone' => '9111111111']);
        $mine->forceFill(['link_flagged_at' => now()])->save();
        $theirs = Contact::create(['tenant_id' => $other->id, 'name' => 'Other Olga', 'phone' => '9222222222']);
        $theirs->forceFill(['link_flagged_at' => now()])->save();

        $this->actingAs($admin)->get(route('tenant.loyalty.needs-review'))
            ->assertOk()
            ->assertSee('Flagged Fred')
            ->assertDontSee('Other Olga');
    }

    public function test_needs_review_is_forbidden_when_the_portal_is_off(): void
    {
        $tenant = $this->shop('S', true, false);
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $this->actingAs($admin)->get(route('tenant.loyalty.needs-review'))->assertForbidden();
    }

    public function test_dismissing_clears_the_flag(): void
    {
        $tenant  = $this->shop();
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = Contact::create(['tenant_id' => $tenant->id, 'name' => 'Flagged Fred', 'phone' => '9111111111']);
        $contact->forceFill(['link_flagged_at' => now()])->save();

        $this->actingAs($admin)->post(route('tenant.loyalty.needs-review.dismiss', $contact->id))
            ->assertRedirect(route('tenant.loyalty.needs-review'));

        $this->assertNull($contact->fresh()->link_flagged_at);
    }

    public function test_a_tenant_cannot_dismiss_another_tenants_contact(): void
    {
        $tenant  = $this->shop();
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $other   = $this->shop('Other');
        $contact = Contact::create(['tenant_id' => $other->id, 'name' => 'Theirs', 'phone' => '9222222222']);
        $contact->forceFill(['link_flagged_at' => now()])->save();

        $this->actingAs($admin)->post(route('tenant.loyalty.needs-review.dismiss', $contact->id))->assertNotFound();

        $this->assertNotNull($contact->fresh()->link_flagged_at);
    }

    public function test_fixing_the_phone_number_resolves_the_flag(): void
    {
        $tenant  = $this->shop();
        $contact = Contact::create(['tenant_id' => $tenant->id, 'name' => 'Flagged Fred', 'phone' => '9111111111']);
        $contact->forceFill(['link_flagged_at' => now()])->save();

        $contact->update(['name' => 'Fred K']);
        $this->assertNotNull($contact->fresh()->link_flagged_at);

        $contact->update(['phone' => '9333333333']);
        $this->assertNull($contact->fresh()->link_flagged_at);
    }

    public function test_contact_panel_shows_the_customer_account_status_only_when_the_portal_is_on(): void
    {
        $tenant   = $this->shop();
        $admin    = $this->makeUser($tenant, 'tenant_admin');
        $customer = $this->customer();
        $contact  = $this->linkedContact($tenant, $customer);

        $this->actingAs($admin)->get(route('tenant.contacts.show', $contact->id))
            ->assertOk()
            ->assertSee('Customer account')
            ->assertSee('Linked');

        $this->configure($tenant, true, false);

        // fresh(): the first request cached $admin->tenant with the old module flags.
        $this->actingAs($admin->fresh())->get(route('tenant.contacts.show', $contact->id))
            ->assertOk()
            ->assertDontSee('Customer account');
    }

    // ── Existing public rewards page ───────────────────────────

    public function test_rewards_page_links_to_the_wallet_only_when_the_portal_is_on(): void
    {
        $tenant = $this->shop();
        $token  = 'rwd_' . Str::random(20);
        $settings = $tenant->settings;
        $settings['loyalty']['public_lookup'] = true;
        $settings['rewards_token'] = $token;
        $tenant->update(['settings' => $settings]);

        $this->get(route('public.rewards.show', $token))->assertOk()->assertSee('see all your rewards in one place');

        $this->configure($tenant->fresh(), true, false);

        $this->get(route('public.rewards.show', $token))->assertOk()->assertDontSee('see all your rewards in one place');
    }
}
