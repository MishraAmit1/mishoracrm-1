<?php

namespace Tests\Feature\SuperAdmin;

use App\Mail\SubscriptionInvoiceMail;
use App\Models\Plan;
use App\Models\PlatformSetting;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\RazorpayService;
use App\Services\SubscriptionInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

// Platform-billing tax invoice: FY-scoped numbering, GST place-of-supply
// split, PDF download (tenant + superadmin), auto issue+deliver on payment,
// resend, and the historical backfill command.
class PlatformInvoiceTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function plan(array $overrides = []): Plan
    {
        return Plan::create(array_merge([
            'name' => 'Pro', 'slug' => 'pro-' . uniqid(),
            'monthly_price' => 1500, 'yearly_price' => 15000,
            'features' => ['users' => 10], 'is_active' => true, 'sort_order' => 1,
        ], $overrides));
    }

    private function paidSub(Tenant $tenant, Plan $plan, array $overrides = []): Subscription
    {
        return Subscription::create(array_merge([
            'tenant_id' => $tenant->id, 'plan_id' => $plan->id,
            'status' => 'active', 'billing_cycle' => 'yearly',
            'original_amount' => 15000, 'discount_amount' => 0,
            'gst_percentage' => 18, 'gst_amount' => 2700, 'total_amount' => 17700,
            'started_at' => now(), 'ends_at' => now()->addYear(),
            'razorpay_order_id' => 'order_' . uniqid(),
            'razorpay_payment_id' => 'pay_' . uniqid(),
        ], $overrides));
    }

    private function sellerProfile(string $state = 'Karnataka'): void
    {
        PlatformSetting::set('billing_legal_name', 'Mishora CRM Technologies Pvt. Ltd.');
        PlatformSetting::set('billing_state', $state);
        PlatformSetting::set('billing_gstin', '29ABCDE1234F1Z5');
        PlatformSetting::set('billing_email', 'billing@mishoracrm.test');
        PlatformSetting::set('invoice_prefix', 'MC');
    }

    public function test_issue_assigns_a_financial_year_scoped_sequential_number(): void
    {
        $this->sellerProfile();
        $tenant = $this->setUpTenant();
        $plan   = $this->plan();
        $svc    = app(SubscriptionInvoiceService::class);

        $a = $svc->issue($this->paidSub($tenant, $plan, ['started_at' => '2026-06-01']));
        $b = $svc->issue($this->paidSub($tenant, $plan, ['started_at' => '2026-07-01']));
        $c = $svc->issue($this->paidSub($tenant, $plan, ['started_at' => '2027-05-01']));

        $this->assertSame('MC/26-27/00001', $a->invoice_number);
        $this->assertSame('MC/26-27/00002', $b->invoice_number);
        $this->assertSame('MC/27-28/00001', $c->invoice_number);
        $this->assertNotNull($a->invoice_issued_at);
    }

    public function test_issue_is_idempotent(): void
    {
        $this->sellerProfile();
        $tenant = $this->setUpTenant();
        $sub    = $this->paidSub($tenant, $this->plan());
        $svc    = app(SubscriptionInvoiceService::class);

        $first  = $svc->issue($sub)->invoice_number;
        $second = $svc->issue($sub->fresh())->invoice_number;

        $this->assertSame($first, $second);
    }

    public function test_free_subscription_is_not_invoiceable(): void
    {
        $tenant = $this->setUpTenant();
        $free   = Subscription::create([
            'tenant_id' => $tenant->id, 'plan_id' => $this->plan(['monthly_price' => 0, 'yearly_price' => 0])->id,
            'status' => 'active', 'billing_cycle' => 'monthly',
            'total_amount' => 0, 'original_amount' => 0,
        ]);

        $this->assertFalse($free->isInvoiceable());
    }

    // The tenant is on a paid plan (bought before invoicing existed) — the
    // row carries no Razorpay id or stored amount, but they still get an
    // invoice, rebuilt from the plan price.
    public function test_active_paid_plan_with_no_payment_metadata_is_invoiceable(): void
    {
        $this->sellerProfile();
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $sub    = $this->paidSub($tenant, $this->plan(['yearly_price' => 15000]), [
            'razorpay_payment_id' => null, 'razorpay_order_id' => null,
            'original_amount' => null, 'total_amount' => 0, 'gst_amount' => 0, 'gst_percentage' => 0,
        ]);

        $this->assertTrue($sub->isInvoiceable());

        $this->actingAs($admin)
            ->get(route('tenant.subscription.invoice', $sub))
            ->assertOk();

        $data = app(SubscriptionInvoiceService::class)->invoiceData($sub->fresh());
        $this->assertEqualsWithDelta(15000, $data['total_amount'], 0.01);
    }

    public function test_trial_and_pending_checkout_are_not_invoiceable(): void
    {
        $tenant = $this->setUpTenant();
        $plan   = $this->plan();

        $trial = Subscription::create([
            'tenant_id' => $tenant->id, 'plan_id' => $plan->id,
            'status' => 'trial', 'billing_cycle' => 'monthly',
            'trial_ends_at' => now()->addDays(10),
        ]);
        $pending = Subscription::create([
            'tenant_id' => $tenant->id, 'plan_id' => $plan->id,
            'status' => 'pending_payment', 'billing_cycle' => 'yearly',
            'total_amount' => 17700,
        ]);

        $this->assertFalse($trial->isInvoiceable());
        $this->assertFalse($pending->isInvoiceable());
    }

    public function test_intra_state_supply_splits_into_cgst_and_sgst(): void
    {
        $this->sellerProfile('Karnataka');
        $tenant = $this->setUpTenant();
        $tenant->update(['settings' => ['state' => 'Karnataka', 'gst' => '29AAAAA0000A1Z5']]);

        $data = app(SubscriptionInvoiceService::class)->invoiceData($this->paidSub($tenant, $this->plan()));

        $this->assertFalse($data['is_inter_state']);
        $this->assertEqualsWithDelta(1350, $data['cgst_amount'], 0.01);
        $this->assertEqualsWithDelta(1350, $data['sgst_amount'], 0.01);
        $this->assertSame(0.0, $data['igst_amount']);
    }

    public function test_inter_state_supply_uses_igst(): void
    {
        $this->sellerProfile('Karnataka');
        $tenant = $this->setUpTenant();
        $tenant->update(['settings' => ['state' => 'Maharashtra', 'gst' => '27AAAAA0000A1Z5']]);

        $data = app(SubscriptionInvoiceService::class)->invoiceData($this->paidSub($tenant, $this->plan()));

        $this->assertTrue($data['is_inter_state']);
        $this->assertEqualsWithDelta(2700, $data['igst_amount'], 0.01);
        $this->assertSame(0.0, $data['cgst_amount']);
        $this->assertSame('Maharashtra', $data['place_of_supply']);
    }

    public function test_legacy_row_without_stored_totals_is_reconstructed_from_the_plan(): void
    {
        $this->sellerProfile();
        $tenant = $this->setUpTenant();
        $sub    = $this->paidSub($tenant, $this->plan(['yearly_price' => 9000]), [
            'original_amount' => 0, 'gst_amount' => 0, 'total_amount' => 0, 'gst_percentage' => 0,
        ]);

        $data = app(SubscriptionInvoiceService::class)->invoiceData($sub);

        $this->assertEqualsWithDelta(9000, $data['taxable_amount'], 0.01);
        $this->assertEqualsWithDelta(9000, $data['total_amount'], 0.01);
        $this->assertSame(0.0, $data['gst_amount']);
    }

    public function test_tenant_admin_can_download_their_invoice_pdf(): void
    {
        $this->sellerProfile();
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $sub    = $this->paidSub($tenant, $this->plan());

        $res = $this->actingAs($admin)->get(route('tenant.subscription.invoice', $sub));

        $res->assertOk();
        $this->assertSame('application/pdf', $res->headers->get('content-type'));
        $this->assertStringContainsString('.pdf', $res->headers->get('content-disposition'));
        $this->assertNotNull($sub->fresh()->invoice_number);
    }

    public function test_a_tenant_cannot_download_another_tenants_invoice(): void
    {
        $this->sellerProfile();
        $mine    = $this->setUpTenant();
        $admin   = $this->makeUser($mine, 'tenant_admin');
        $other   = Tenant::factory()->create();
        $foreign = $this->paidSub($other, $this->plan());

        $this->actingAs($admin)
            ->get(route('tenant.subscription.invoice', $foreign))
            ->assertNotFound();
    }

    public function test_non_admin_staff_cannot_reach_the_invoice_route(): void
    {
        $this->sellerProfile();
        $tenant = $this->setUpTenant();
        $staff  = $this->makeUser($tenant, 'staff');
        $sub    = $this->paidSub($tenant, $this->plan());

        $this->actingAs($staff)
            ->get(route('tenant.subscription.invoice', $sub))
            ->assertForbidden();
    }

    public function test_superadmin_can_download_and_resend_a_tenant_invoice(): void
    {
        Mail::fake();
        $this->sellerProfile();
        $tenant = $this->setUpTenant();
        $super  = $this->makeUser($tenant, 'superadmin');
        $this->makeUser($tenant, 'tenant_admin');
        $sub    = $this->paidSub($tenant, $this->plan());

        $this->actingAs($super)
            ->get(route('superadmin.tenants.invoice-download', [$tenant, $sub]))
            ->assertOk();

        $this->actingAs($super)
            ->post(route('superadmin.tenants.invoice-resend', [$tenant, $sub]))
            ->assertRedirect();

        Mail::assertSent(SubscriptionInvoiceMail::class);
        $this->assertDatabaseHas('audit_logs', ['action' => 'subscription_invoice_resent', 'model_id' => $sub->id]);
    }

    public function test_paying_via_razorpay_issues_and_emails_the_invoice(): void
    {
        Mail::fake();
        $this->sellerProfile();
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $plan   = $this->plan();

        $sub = Subscription::create([
            'tenant_id' => $tenant->id, 'plan_id' => $plan->id,
            'status' => 'pending_payment', 'billing_cycle' => 'yearly',
            'original_amount' => 15000, 'gst_percentage' => 18, 'gst_amount' => 2700, 'total_amount' => 17700,
            'razorpay_order_id' => 'order_ABC123',
            'started_at' => now(), 'ends_at' => now()->addYear(),
        ]);

        $this->mock(RazorpayService::class, function ($m) {
            $m->shouldReceive('verifyPaymentSignature')->andReturn(true);
        });

        $this->actingAs($admin)->post(route('tenant.subscription.verify'), [
            'razorpay_order_id'   => 'order_ABC123',
            'razorpay_payment_id' => 'pay_XYZ789',
            'razorpay_signature'  => 'sig_test',
        ])->assertRedirect(route('tenant.subscription.success'));

        $sub->refresh();
        $this->assertSame('active', $sub->status);
        $this->assertNotNull($sub->invoice_number);
        Mail::assertSent(SubscriptionInvoiceMail::class, fn ($mail) => $mail->hasTo($admin->email));
    }

    public function test_backfill_command_numbers_historical_paid_subscriptions(): void
    {
        $this->sellerProfile();
        $tenant = $this->setUpTenant();
        $plan   = $this->plan();

        $old  = $this->paidSub($tenant, $plan, ['started_at' => now()->subMonths(3)]);
        $new  = $this->paidSub($tenant, $plan, ['started_at' => now()->subMonth()]);
        $free = Subscription::create([
            'tenant_id' => $tenant->id, 'plan_id' => $plan->id, 'status' => 'active',
            'billing_cycle' => 'monthly', 'total_amount' => 0, 'original_amount' => 0,
        ]);

        $this->artisan('invoices:backfill-subscriptions')->assertExitCode(0);

        $this->assertNotNull($old->fresh()->invoice_number);
        $this->assertNotNull($new->fresh()->invoice_number);
        $this->assertNull($free->fresh()->invoice_number);

        $this->assertLessThan(
            (int) substr($new->fresh()->invoice_number, -5),
            (int) substr($old->fresh()->invoice_number, -5),
        );
    }

    public function test_backfill_dry_run_writes_nothing(): void
    {
        $this->sellerProfile();
        $tenant = $this->setUpTenant();
        $sub    = $this->paidSub($tenant, $this->plan());

        $this->artisan('invoices:backfill-subscriptions --dry-run')->assertExitCode(0);

        $this->assertNull($sub->fresh()->invoice_number);
    }

    public function test_superadmin_can_save_the_billing_profile(): void
    {
        $tenant = $this->setUpTenant();
        $super  = $this->makeUser($tenant, 'superadmin');

        $this->actingAs($super)->put(route('superadmin.billing-profile.update'), [
            'billing_legal_name' => 'Mishora CRM Technologies Pvt. Ltd.',
            'billing_state'      => 'Karnataka',
            'billing_gstin'      => '29ABCDE1234F1Z5',
            'invoice_prefix'     => 'MC',
            'platform_wa_enabled' => '1',
            'platform_wa_phone_number_id' => '1234567890',
            'platform_wa_access_token'    => 'tok_secret',
        ])->assertRedirect();

        $this->assertSame('Mishora CRM Technologies Pvt. Ltd.', PlatformSetting::get('billing_legal_name'));
        $this->assertSame('MC', PlatformSetting::get('invoice_prefix'));
        $this->assertSame('1', PlatformSetting::get('platform_wa_enabled'));
        $this->assertSame('tok_secret', PlatformSetting::get('platform_wa_access_token'));
    }

    public function test_blank_whatsapp_token_keeps_the_saved_one(): void
    {
        PlatformSetting::set('platform_wa_access_token', 'existing_tok');
        $tenant = $this->setUpTenant();
        $super  = $this->makeUser($tenant, 'superadmin');

        $this->actingAs($super)->put(route('superadmin.billing-profile.update'), [
            'billing_legal_name' => 'X',
            'platform_wa_access_token' => '',
        ])->assertRedirect();

        $this->assertSame('existing_tok', PlatformSetting::get('platform_wa_access_token'));
    }
}
