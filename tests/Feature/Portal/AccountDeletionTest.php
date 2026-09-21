<?php

namespace Tests\Feature\Portal;

use App\Models\Contact;
use App\Models\Customer;
use App\Models\CustomerOtp;
use App\Models\Tenant;
use App\Models\WhatsappSetting;
use App\Services\LoyaltyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class AccountDeletionTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function shop(string $name = 'Salon'): Tenant
    {
        $tenant   = $this->setUpTenant();
        $settings = $tenant->settings ?? [];
        $settings['modules']['loyalty']         = true;
        $settings['modules']['customer_portal'] = true;
        $tenant->update(['settings' => $settings, 'name' => $name]);

        return $tenant->fresh();
    }

    private function customer(string $phone = '9876543210'): Customer
    {
        $customer = Customer::create(['phone' => $phone, 'pin_hash' => Hash::make('1234')]);
        $customer->forceFill(['name' => 'Ravi Kumar', 'email' => 'ravi@example.com', 'email_verified_at' => now()])->save();

        return $customer;
    }

    private function member(Tenant $tenant, Customer $customer, int $points = 300): Contact
    {
        $contact = Contact::create(['tenant_id' => $tenant->id, 'name' => 'Ravi', 'phone' => $customer->phone]);
        $contact->forceFill(['customer_id' => $customer->id, 'phone_verified' => true])->save();
        app(LoyaltyService::class)->manualAdjust($contact->fresh(), $points, 'seed');

        return $contact->fresh();
    }

    private function as(Customer $customer): static
    {
        $this->app['auth']->guard('customer')->setUser($customer);

        return $this;
    }

    // ── Confirmation ───────────────────────────────────────────

    public function test_profile_page_offers_deletion(): void
    {
        $this->as($this->customer())->get(route('portal.profile.edit'))
            ->assertOk()
            ->assertSee('Delete my account')
            ->assertSee(route('portal.account.delete'), false);
    }

    public function test_deletion_needs_the_word_delete(): void
    {
        $customer = $this->customer();

        foreach ([[], ['confirm' => ''], ['confirm' => 'delete'], ['confirm' => 'yes']] as $payload) {
            $this->as($customer)->post(route('portal.account.delete'), $payload)->assertSessionHasErrors('confirm');
        }

        $this->assertFalse($customer->fresh()->trashed());
    }

    public function test_deletion_requires_a_login(): void
    {
        $this->post(route('portal.account.delete'), ['confirm' => 'DELETE'])->assertRedirect(route('portal.login'));
    }

    // ── What gets deleted, what stays ──────────────────────────

    public function test_the_identity_is_wiped_and_soft_deleted(): void
    {
        $customer = $this->customer();
        $this->member($this->shop(), $customer);
        CustomerOtp::issue($customer, $customer->phone, 'whatsapp', null);

        $this->as($customer)->post(route('portal.account.delete'), ['confirm' => 'DELETE'])
            ->assertRedirect(route('portal.login'))
            ->assertSessionHas('success');

        $row = Customer::withTrashed()->find($customer->id);
        $this->assertTrue($row->trashed());
        $this->assertNull($row->name);
        $this->assertNull($row->email);
        $this->assertNull($row->email_verified_at);
        $this->assertNull($row->pin_hash);
        $this->assertSame(0, CustomerOtp::where('phone', $customer->phone)->count());
        $this->assertSame($customer->phone, $row->phone);   // kept so a re-signup restores cleanly
    }

    public function test_the_customer_is_signed_out_and_cannot_come_back_to_the_wallet(): void
    {
        $customer = $this->customer();

        $this->as($customer)->post(route('portal.account.delete'), ['confirm' => 'DELETE']);

        $this->assertGuest('customer');
        $this->get('/wallet')->assertRedirect(route('portal.login'));
    }

    public function test_shops_keep_their_contacts_points_and_history_but_lose_the_link(): void
    {
        $customer = $this->customer();
        $tenant   = $this->shop();
        $contact  = $this->member($tenant, $customer, 300);

        $this->as($customer)->post(route('portal.account.delete'), ['confirm' => 'DELETE']);

        $fresh = Contact::withoutGlobalScopes()->find($contact->id);
        $this->assertNotNull($fresh);
        $this->assertNull($fresh->customer_id);
        $this->assertFalse($fresh->phone_verified);
        $this->assertSame(300, $fresh->loyalty_points);
        $this->assertSame('Ravi', $fresh->name);
        $this->assertSame('9876543210', $fresh->phone);
        $this->assertDatabaseHas('loyalty_transactions', ['contact_id' => $contact->id, 'description' => 'seed']);
    }

    public function test_it_unlinks_every_shop_but_leaves_other_customers_alone(): void
    {
        $ravi   = $this->customer('9876543210');
        $sunita = $this->customer('9123456789');
        $a      = $this->shop('A');
        $b      = $this->shop('B');

        $raviA   = $this->member($a, $ravi);
        $raviB   = $this->member($b, $ravi);
        $sunitaA = $this->member($a, $sunita);

        $this->as($ravi)->post(route('portal.account.delete'), ['confirm' => 'DELETE']);

        $this->assertNull($raviA->fresh()->customer_id);
        $this->assertNull($raviB->fresh()->customer_id);
        $this->assertSame($sunita->id, $sunitaA->fresh()->customer_id);
        $this->assertTrue($sunitaA->fresh()->phone_verified);
        $this->assertFalse($sunita->fresh()->trashed());
    }

    public function test_the_deletion_is_not_written_to_a_tenants_audit_log(): void
    {
        $customer = $this->customer();
        $this->member($this->shop(), $customer);
        $before = \App\Models\AuditLog::count();

        $this->as($customer)->post(route('portal.account.delete'), ['confirm' => 'DELETE']);

        $this->assertSame($before, \App\Models\AuditLog::count());
    }

    // ── Coming back ────────────────────────────────────────────

    public function test_signing_in_again_restores_the_account_and_relinks_the_shop(): void
    {
        Http::fake();
        $customer = $this->customer();
        $tenant   = $this->shop();
        WhatsappSetting::create(['tenant_id' => $tenant->id, 'phone_number_id' => '1', 'access_token' => 't', 'is_connected' => true]);
        $contact = $this->member($tenant, $customer, 30);           // thin contact: relinks straight away

        $this->as($customer)->post(route('portal.account.delete'), ['confirm' => 'DELETE']);
        $this->assertNull($contact->fresh()->customer_id);

        $this->post(route('portal.login.request-otp'), ['phone' => '9876543210'])->assertOk();
        $body = Http::recorded()->last()[0]->data()['text']['body'];
        preg_match('/\b(\d{6})\b/', $body, $m);

        $this->post(route('portal.login.verify'), ['phone' => '9876543210', 'code' => $m[1]])->assertRedirect();

        $restored = Customer::find($customer->id);
        $this->assertNotNull($restored);
        $this->assertNull($restored->name);                          // the old profile stays gone
        $this->assertNull($restored->pin_hash);
        $this->assertSame($restored->id, $contact->fresh()->customer_id);
    }
}
