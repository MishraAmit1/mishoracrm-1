<?php

namespace Tests\Feature\Portal;

use App\Models\Contact;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Tenant;
use App\Services\CustomerLinkService;
use App\Services\LoyaltyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class CustomerLinkingTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function enablePortal(Tenant $tenant, bool $loyalty = true, bool $portal = true): Tenant
    {
        $settings = $tenant->settings ?? [];
        $settings['modules']['loyalty']         = $loyalty;
        $settings['modules']['customer_portal'] = $portal;
        $tenant->update(['settings' => $settings]);

        return $tenant->fresh();
    }

    private function contact(Tenant $tenant, string $phone = '9876543210', ?string $email = null): Contact
    {
        return Contact::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Ravi',
            'phone'     => $phone,
            'email'     => $email,
        ]);
    }

    private function service(): CustomerLinkService
    {
        return app(CustomerLinkService::class);
    }

    // ── linkByPhone ────────────────────────────────────────────

    public function test_thin_contact_auto_links_and_is_verified(): void
    {
        $tenant  = $this->enablePortal($this->setUpTenant());
        $contact = $this->contact($tenant);

        $customer = Customer::create(['phone' => '9876543210']);
        $result   = $this->service()->linkByPhone($customer);

        $this->assertSame(['linked' => 1, 'pending' => 0], $result);
        $contact->refresh();
        $this->assertSame($customer->id, $contact->customer_id);
        $this->assertTrue($contact->phone_verified);
    }

    public function test_contact_with_paid_invoice_links_but_stays_pending(): void
    {
        $tenant  = $this->enablePortal($this->setUpTenant());
        $contact = $this->contact($tenant);

        Invoice::create([
            'tenant_id'   => $tenant->id,
            'contact_id'  => $contact->id,
            'number'      => Invoice::generateNumber($tenant->id),
            'date'        => now()->toDateString(),
            'due_date'    => now()->addDays(7)->toDateString(),
            'items'       => [['description' => 'Item', 'quantity' => 1, 'rate' => 500]],
            'subtotal'    => 500,
            'total'       => 500,
            'paid_amount' => 500,
            'status'      => 'paid',
        ]);

        $customer = Customer::create(['phone' => '9876543210']);
        $result   = $this->service()->linkByPhone($customer);

        $this->assertSame(['linked' => 0, 'pending' => 1], $result);
        $contact->refresh();
        $this->assertSame($customer->id, $contact->customer_id);
        $this->assertFalse($contact->phone_verified);
    }

    public function test_high_lifetime_points_contact_links_but_stays_pending(): void
    {
        $tenant  = $this->enablePortal($this->setUpTenant());
        $contact = $this->contact($tenant);

        app(LoyaltyService::class)->manualAdjust($contact->fresh(), 500, 'seed');

        $customer = Customer::create(['phone' => '9876543210']);
        $result   = $this->service()->linkByPhone($customer);

        $this->assertSame(['linked' => 0, 'pending' => 1], $result);
        $this->assertFalse($contact->fresh()->phone_verified);
    }

    public function test_links_by_verified_email_when_phone_does_not_match(): void
    {
        $tenant  = $this->enablePortal($this->setUpTenant());
        $contact = $this->contact($tenant, '9111111111', 'ravi@example.com');

        $customer = Customer::create(['phone' => '9222222222', 'email' => 'Ravi@Example.com']);
        $customer->forceFill(['email_verified_at' => now()])->save();
        $result = $this->service()->linkByPhone($customer);

        $this->assertSame(['linked' => 1, 'pending' => 0], $result);
        $this->assertSame($customer->id, $contact->fresh()->customer_id);
    }

    public function test_an_unverified_email_never_links_anything(): void
    {
        $tenant  = $this->enablePortal($this->setUpTenant());
        $contact = $this->contact($tenant, '9111111111', 'victim@example.com');

        // Anyone can type any email into their profile — it proves nothing.
        $attacker = Customer::create(['phone' => '9222222222', 'email' => 'victim@example.com']);

        $this->assertSame(['linked' => 0, 'pending' => 0], $this->service()->linkByPhone($attacker));
        $this->assertNull($contact->fresh()->customer_id);

        $newContact = $this->contact($tenant, '9333333333', 'victim@example.com');
        $this->service()->attachContactToCustomer($newContact);
        $this->assertNull($newContact->fresh()->customer_id);
    }

    public function test_attach_links_a_new_contact_by_verified_email(): void
    {
        $tenant   = $this->enablePortal($this->setUpTenant());
        $customer = Customer::create(['phone' => '9222222222', 'email' => 'ravi@example.com']);
        $customer->forceFill(['email_verified_at' => now()])->save();

        $contact = $this->contact($tenant, '9111111111', 'RAVI@example.com');
        $this->service()->attachContactToCustomer($contact);

        $this->assertSame($customer->id, $contact->fresh()->customer_id);
    }

    public function test_cross_tenant_many_to_one_linking(): void
    {
        $tenantA = $this->enablePortal($this->setUpTenant());
        $tenantB = $this->enablePortal(Tenant::factory()->create());
        $this->giveActiveSubscription($tenantB);

        $contactA = $this->contact($tenantA);
        $contactB = $this->contact($tenantB);

        $customer = Customer::create(['phone' => '9876543210']);
        $result   = $this->service()->linkByPhone($customer);

        $this->assertSame(['linked' => 2, 'pending' => 0], $result);
        $this->assertSame($customer->id, $contactA->fresh()->customer_id);
        $this->assertSame($customer->id, $contactB->fresh()->customer_id);
    }

    public function test_already_linked_contacts_are_skipped(): void
    {
        $tenant  = $this->enablePortal($this->setUpTenant());
        $contact = $this->contact($tenant);

        $otherCustomer = Customer::create(['phone' => '9111111111']);
        $contact->forceFill(['customer_id' => $otherCustomer->id, 'phone_verified' => true])->save();

        $customer = Customer::create(['phone' => '9876543210']);
        $result   = $this->service()->linkByPhone($customer);

        $this->assertSame(['linked' => 0, 'pending' => 0], $result);
        $this->assertSame($otherCustomer->id, $contact->fresh()->customer_id);
    }

    // ── attachContactToCustomer ───────────────────────────────

    public function test_attach_links_new_contact_to_existing_customer(): void
    {
        $tenant   = $this->enablePortal($this->setUpTenant());
        $customer = Customer::create(['phone' => '9876543210']);

        $contact = $this->contact($tenant);
        $this->service()->attachContactToCustomer($contact);

        $this->assertSame($customer->id, $contact->fresh()->customer_id);
        $this->assertTrue($contact->fresh()->phone_verified);
    }

    public function test_attach_never_creates_a_customer(): void
    {
        $tenant  = $this->enablePortal($this->setUpTenant());
        $contact = $this->contact($tenant, '9000000001');

        $this->service()->attachContactToCustomer($contact);

        $this->assertSame(0, Customer::count());
        $this->assertNull($contact->fresh()->customer_id);
    }

    // ── confirmContact ─────────────────────────────────────────

    public function test_confirm_yes_verifies_the_pending_contact(): void
    {
        $tenant   = $this->enablePortal($this->setUpTenant());
        $contact  = $this->contact($tenant);
        $customer = Customer::create(['phone' => '9876543210']);
        $contact->forceFill(['customer_id' => $customer->id, 'phone_verified' => false])->save();

        $this->service()->confirmContact($customer, $contact, true);

        $this->assertTrue($contact->fresh()->phone_verified);
        $this->assertSame($customer->id, $contact->fresh()->customer_id);
    }

    public function test_confirm_not_me_unlinks_and_flags_and_notifies_tenant(): void
    {
        $tenant  = $this->enablePortal($this->setUpTenant());
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = $this->contact($tenant);
        $customer = Customer::create(['phone' => '9876543210']);
        $contact->forceFill(['customer_id' => $customer->id, 'phone_verified' => false])->save();

        $this->service()->confirmContact($customer, $contact, false);

        $fresh = $contact->fresh();
        $this->assertNull($fresh->customer_id);
        $this->assertFalse($fresh->phone_verified);
        $this->assertNotNull($fresh->link_flagged_at);

        $this->assertDatabaseHas('notifications', [
            'tenant_id' => $tenant->id,
            'user_id'   => $admin->id,
            'type'      => 'loyalty.wrong_number',
        ]);
    }

    public function test_confirm_ignores_a_contact_belonging_to_another_customer(): void
    {
        $tenant   = $this->enablePortal($this->setUpTenant());
        $contact  = $this->contact($tenant);
        $owner    = Customer::create(['phone' => '9876543210']);
        $stranger = Customer::create(['phone' => '9111111111']);
        $contact->forceFill(['customer_id' => $owner->id, 'phone_verified' => false])->save();

        $this->service()->confirmContact($stranger, $contact, true);

        $this->assertFalse($contact->fresh()->phone_verified);
        $this->assertSame($owner->id, $contact->fresh()->customer_id);
    }

    // ── module tri-state round-trip ───────────────────────────

    public function test_customer_portal_module_tristate_round_trips(): void
    {
        $tenant = $this->setUpTenant();
        $this->assertFalse($tenant->hasModuleEnabled('customer_portal'));

        $tenant = $this->enablePortal($tenant);
        $this->assertTrue($tenant->hasModuleEnabled('customer_portal'));

        $settings = $tenant->settings;
        $settings['modules']['customer_portal'] = false;
        $tenant->update(['settings' => $settings]);
        $this->assertFalse($tenant->fresh()->hasModuleEnabled('customer_portal'));
    }
}
