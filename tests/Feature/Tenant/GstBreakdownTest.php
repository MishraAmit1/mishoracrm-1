<?php

namespace Tests\Feature\Tenant;

use App\Models\Contact;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\Tenant;
use App\Models\Vendor;
use App\Models\VendorBill;
use App\Services\GstService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class GstBreakdownTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function tenantInState(string $code): Tenant
    {
        $tenant = $this->setUpTenant();
        $tenant->update(['settings' => array_merge($tenant->settings ?? [], ['state' => $code])]);

        return $tenant;
    }

    public function test_gst_service_splits_intra_state_into_cgst_sgst(): void
    {
        $b = GstService::breakdown(180.0, 'KA', 'KA');

        $this->assertFalse($b['is_inter_state']);
        $this->assertEqualsWithDelta(90.0, $b['cgst_amount'], 0.01);
        $this->assertEqualsWithDelta(90.0, $b['sgst_amount'], 0.01);
        $this->assertEqualsWithDelta(0.0, $b['igst_amount'], 0.01);
    }

    public function test_gst_service_uses_igst_for_inter_state(): void
    {
        $b = GstService::breakdown(180.0, 'KA', 'MH');

        $this->assertTrue($b['is_inter_state']);
        $this->assertEqualsWithDelta(0.0, $b['cgst_amount'], 0.01);
        $this->assertEqualsWithDelta(180.0, $b['igst_amount'], 0.01);
    }

    public function test_gst_service_resolves_state_names_and_gstins(): void
    {
        $this->assertSame('KA', GstService::stateCode('Karnataka'));
        $this->assertSame('KA', GstService::stateCode('ka'));
        $this->assertSame('KA', GstService::stateCode('29AABCU9603R1ZM')); // 29 = Karnataka
        $this->assertSame('MH', GstService::stateCode('27AAAAA0000A1Z5'));
        $this->assertNull(GstService::stateCode('Atlantis'));
    }

    public function test_unknown_states_default_to_intra_state(): void
    {
        $b = GstService::breakdown(100.0, null, 'KA');
        $this->assertFalse($b['is_inter_state']);
        $this->assertEqualsWithDelta(50.0, $b['cgst_amount'], 0.01);
    }

    public function test_invoice_stores_the_split_from_tenant_and_contact_state(): void
    {
        $tenant  = $this->tenantInState('KA');
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = Contact::create(['tenant_id' => $tenant->id, 'name' => 'Acme', 'phone' => '9999999999', 'state' => 'MH']);

        $this->actingAs($admin)->post(route('tenant.invoices.store'), [
            'contact_id' => $contact->id,
            'date'       => now()->toDateString(),
            'due_date'   => now()->addDays(7)->toDateString(),
            'items'      => [['description' => 'Widget', 'quantity' => 1, 'rate' => 1000]],
            'tax_percent' => 18,
        ]);

        $invoice = Invoice::first();
        $this->assertTrue((bool) $invoice->is_inter_state);
        $this->assertEqualsWithDelta(180.0, (float) $invoice->igst_amount, 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $invoice->cgst_amount, 0.01);
    }

    public function test_purchase_order_splits_from_vendor_and_tenant_state(): void
    {
        $tenant = $this->tenantInState('KA');
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $vendor = Vendor::create(['tenant_id' => $tenant->id, 'name' => 'Local Supplier', 'state' => 'KA']);

        $this->actingAs($admin)->post(route('tenant.purchase-orders.store'), [
            'date'      => now()->toDateString(),
            'vendor_id' => $vendor->id,
            'items'     => [['name' => 'Rod', 'quantity' => 2, 'rate' => 500, 'amount' => 1000, 'tax_percent' => 18]],
        ]);

        $po = PurchaseOrder::first();
        $this->assertFalse((bool) $po->is_inter_state);
        $this->assertEqualsWithDelta(90.0, (float) $po->cgst_amount, 0.01);
        $this->assertEqualsWithDelta(90.0, (float) $po->sgst_amount, 0.01);
    }

    public function test_vendor_bill_splits_inter_state(): void
    {
        $tenant = $this->tenantInState('KA');
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $vendor = Vendor::create(['tenant_id' => $tenant->id, 'name' => 'Outstation Supplier', 'state' => 'TN']);

        $this->actingAs($admin)->post(route('tenant.vendor-bills.store'), [
            'vendor_id' => $vendor->id,
            'date'      => now()->toDateString(),
            'items'     => [['name' => 'Rod', 'quantity' => 10, 'rate' => 100, 'tax_percent' => 18, 'amount' => 1000]],
        ]);

        $bill = VendorBill::first();
        $this->assertTrue((bool) $bill->is_inter_state);
        $this->assertEqualsWithDelta(180.0, (float) $bill->igst_amount, 0.01);
    }

    public function test_gst_lines_helper_falls_back_to_combined_line_for_old_rows(): void
    {
        $tenant = $this->setUpTenant();
        $bill   = VendorBill::factory()->create(['tenant_id' => $tenant->id, 'cgst_amount' => 0, 'sgst_amount' => 0, 'igst_amount' => 0]);

        $lines = $bill->gstLines();
        $this->assertCount(1, $lines);
        $this->assertSame('GST', $lines[0]['label']);
    }
}
