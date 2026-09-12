<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\ContactEnquiry;
use App\Models\PlatformSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class ContactEnquiryAdminTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function enquiry(array $overrides = []): ContactEnquiry
    {
        return ContactEnquiry::create(array_merge([
            'name'    => 'Priya',
            'company' => 'Acme',
            'email'   => 'priya@acme.test',
            'status'  => 'new',
        ], $overrides));
    }

    public function test_superadmin_can_view_the_enquiries_list(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'superadmin');
        $this->enquiry(['company' => 'Distinctive Co']);

        $this->actingAs($admin)
            ->get(route('superadmin.contact-enquiries.index'))
            ->assertOk()
            ->assertSee('Distinctive Co');
    }

    public function test_a_tenant_admin_cannot_access_the_enquiries_list(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $this->actingAs($admin)
            ->get(route('superadmin.contact-enquiries.index'))
            ->assertForbidden();
    }

    public function test_marking_contacted_stamps_the_handler(): void
    {
        $tenant   = $this->setUpTenant();
        $admin    = $this->makeUser($tenant, 'superadmin');
        $enquiry  = $this->enquiry();

        $this->actingAs($admin)
            ->post(route('superadmin.contact-enquiries.status', $enquiry), ['status' => 'contacted'])
            ->assertRedirect();

        $enquiry->refresh();
        $this->assertSame('contacted', $enquiry->status);
        $this->assertSame($admin->id, $enquiry->handled_by);
        $this->assertNotNull($enquiry->handled_at);
    }

    public function test_superadmin_can_delete_an_enquiry(): void
    {
        $tenant  = $this->setUpTenant();
        $admin   = $this->makeUser($tenant, 'superadmin');
        $enquiry = $this->enquiry();

        $this->actingAs($admin)
            ->delete(route('superadmin.contact-enquiries.destroy', $enquiry))
            ->assertRedirect();

        $this->assertDatabaseMissing('contact_enquiries', ['id' => $enquiry->id]);
    }

    public function test_superadmin_can_save_sales_contact_settings(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'superadmin');

        $this->actingAs($admin)
            ->post(route('superadmin.contact-enquiries.settings'), [
                'sales_email'    => 'sales@mishoracrm.test',
                'sales_phone'    => '+91 90000 00000',
                'sales_whatsapp' => '+91 90000 00000',
            ])
            ->assertRedirect();

        $this->assertSame('sales@mishoracrm.test', PlatformSetting::get('sales_email'));
        $this->assertSame('+91 90000 00000', PlatformSetting::get('sales_phone'));
    }
}
