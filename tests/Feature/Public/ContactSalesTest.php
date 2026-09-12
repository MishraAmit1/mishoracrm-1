<?php

namespace Tests\Feature\Public;

use App\Models\ContactEnquiry;
use App\Models\PlatformSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactSalesTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name'      => 'Priya Sharma',
            'company'   => 'Acme Pvt Ltd',
            'email'     => 'priya@acme.test',
            'phone'     => '+91 98765 43210',
            'team_size' => '26–50',
            'message'   => 'We need Manufacturing + Loyalty for 40 staff.',
        ], $overrides);
    }

    public function test_contact_sales_page_renders(): void
    {
        $this->get(route('contact-sales'))
            ->assertOk()
            ->assertSee('name="company"', false)
            ->assertSee('Enterprise');
    }

    public function test_valid_submission_creates_an_enquiry_and_shows_thank_you(): void
    {
        $this->post(route('contact-sales.store'), $this->payload())
            ->assertRedirect()
            ->assertSessionHas('sales_sent', true);

        $this->assertDatabaseHas('contact_enquiries', [
            'email'   => 'priya@acme.test',
            'company' => 'Acme Pvt Ltd',
            'status'  => 'new',
        ]);

        $this->get(route('contact-sales'))->assertSee("Thanks");
    }

    public function test_submission_requires_name_company_and_email(): void
    {
        $this->post(route('contact-sales.store'), [])
            ->assertSessionHasErrors(['name', 'company', 'email']);

        $this->assertSame(0, ContactEnquiry::count());
    }

    public function test_sales_email_setting_receives_a_copy(): void
    {
        PlatformSetting::set('sales_email', 'sales@mishoracrm.test');

        $this->post(route('contact-sales.store'), $this->payload())->assertRedirect();

        $messages = app('mailer')->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('sales@mishoracrm.test', $messages->first()->getOriginalMessage()->toString());
    }

    public function test_no_mail_when_sales_email_is_unset(): void
    {
        $this->post(route('contact-sales.store'), $this->payload())->assertRedirect();

        $this->assertCount(0, app('mailer')->getSymfonyTransport()->messages());
        $this->assertSame(1, ContactEnquiry::count());
    }

    public function test_post_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post(route('contact-sales.store'), $this->payload(['email' => "u$i@x.test"]));
        }

        $this->post(route('contact-sales.store'), $this->payload(['email' => 'over@x.test']))
            ->assertStatus(429);
    }
}
