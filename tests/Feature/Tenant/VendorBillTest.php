<?php

namespace Tests\Feature\Tenant;

use App\Models\PurchaseOrder;
use App\Models\Tenant;
use App\Models\Vendor;
use App\Models\VendorBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class VendorBillTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function payload(Vendor $vendor, array $overrides = []): array
    {
        return array_merge([
            'vendor_id' => $vendor->id,
            'date'      => now()->toDateString(),
            'items'     => [[
                'name'        => 'Steel Rod',
                'quantity'    => 10,
                'rate'        => 100,
                'tax_percent' => 18,
                'amount'      => 1000,
            ]],
        ], $overrides);
    }

    public function test_tenant_admin_can_create_a_vendor_bill(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $vendor = Vendor::create(['tenant_id' => $tenant->id, 'name' => 'Acme Supplies']);

        $this->actingAs($admin)->post(route('tenant.vendor-bills.store'), $this->payload($vendor))
            ->assertRedirect();

        $bill = VendorBill::first();
        $this->assertNotNull($bill);
        $this->assertSame('unpaid', $bill->status);
        $this->assertEqualsWithDelta(1180.0, (float) $bill->total, 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $bill->amount_paid, 0.01);
    }

    public function test_create_form_prefills_from_a_purchase_order(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $vendor = Vendor::create(['tenant_id' => $tenant->id, 'name' => 'Acme Supplies']);
        $po     = PurchaseOrder::factory()->create([
            'tenant_id' => $tenant->id, 'created_by' => $admin->id, 'vendor_id' => $vendor->id,
        ]);

        $this->actingAs($admin)
            ->get(route('tenant.vendor-bills.create', ['purchase_order_id' => $po->id]))
            ->assertOk()
            ->assertSee($po->items[0]['name']);
    }

    public function test_recording_a_partial_then_full_payment_moves_status(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $vendor = Vendor::create(['tenant_id' => $tenant->id, 'name' => 'Acme Supplies']);
        $bill   = VendorBill::factory()->create(['tenant_id' => $tenant->id, 'vendor_id' => $vendor->id, 'created_by' => $admin->id]);
        $total  = (float) $bill->total;

        $this->actingAs($admin)->post(route('tenant.vendor-bills.payments.store', $bill->id), [
            'payments' => [[
                'amount' => round($total / 2, 2), 'method' => 'bank_transfer', 'paid_at' => now()->toDateString(),
            ]],
        ])->assertRedirect();

        $bill->refresh();
        $this->assertSame('partially_paid', $bill->status);
        $this->assertEqualsWithDelta($total / 2, (float) $bill->amount_paid, 0.05);

        $this->actingAs($admin)->post(route('tenant.vendor-bills.payments.store', $bill->id), [
            'payments' => [[
                'amount' => $bill->fresh()->due_amount, 'method' => 'upi', 'paid_at' => now()->toDateString(),
            ]],
        ])->assertRedirect();

        $this->assertSame('paid', $bill->fresh()->status);
    }

    public function test_payment_cannot_exceed_the_balance_due(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $vendor = Vendor::create(['tenant_id' => $tenant->id, 'name' => 'Acme Supplies']);
        $bill   = VendorBill::factory()->create(['tenant_id' => $tenant->id, 'vendor_id' => $vendor->id, 'created_by' => $admin->id]);

        $this->actingAs($admin)->post(route('tenant.vendor-bills.payments.store', $bill->id), [
            'payments' => [['amount' => (float) $bill->total + 500, 'method' => 'cash', 'paid_at' => now()->toDateString()]],
        ]);

        $this->assertEqualsWithDelta(0.0, (float) $bill->fresh()->amount_paid, 0.01);
        $this->assertSame('unpaid', $bill->fresh()->status);
    }

    public function test_a_bill_with_payments_cannot_be_edited(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $vendor = Vendor::create(['tenant_id' => $tenant->id, 'name' => 'Acme Supplies']);
        $bill   = VendorBill::factory()->create(['tenant_id' => $tenant->id, 'vendor_id' => $vendor->id, 'created_by' => $admin->id]);

        $this->actingAs($admin)->post(route('tenant.vendor-bills.payments.store', $bill->id), [
            'payments' => [['amount' => 100, 'method' => 'cash', 'paid_at' => now()->toDateString()]],
        ]);

        $this->actingAs($admin)->get(route('tenant.vendor-bills.edit', $bill->id))
            ->assertRedirect(route('tenant.vendor-bills.show', $bill->id));
    }

    public function test_cancelling_a_bill_drops_it_from_vendor_outstanding(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $vendor = Vendor::create(['tenant_id' => $tenant->id, 'name' => 'Acme Supplies']);
        $bill   = VendorBill::factory()->create(['tenant_id' => $tenant->id, 'vendor_id' => $vendor->id, 'created_by' => $admin->id]);

        $this->assertGreaterThan(0, $vendor->outstandingAmount());

        $this->actingAs($admin)->post(route('tenant.vendor-bills.cancel', $bill->id))->assertRedirect();

        $this->assertSame('cancelled', $bill->fresh()->status);
        $this->assertEqualsWithDelta(0.0, $vendor->fresh()->outstandingAmount(), 0.01);
    }

    public function test_due_date_seeded_from_vendor_payment_terms_is_accepted(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $vendor = Vendor::create(['tenant_id' => $tenant->id, 'name' => 'Acme Supplies', 'payment_terms_days' => 15]);

        $this->actingAs($admin)->post(route('tenant.vendor-bills.store'), $this->payload($vendor, [
            'due_date' => now()->addDays(15)->toDateString(),
        ]))->assertRedirect();

        $this->assertSame(now()->addDays(15)->toDateString(), VendorBill::first()->due_date->toDateString());
    }

    public function test_a_vendor_bill_from_another_tenant_returns_404(): void
    {
        $tenantA = $this->setUpTenant();
        $tenantB = Tenant::factory()->create();
        $adminA  = $this->makeUser($tenantA, 'tenant_admin');
        $billB   = VendorBill::factory()->create(['tenant_id' => $tenantB->id]);

        $this->actingAs($adminA)->get(route('tenant.vendor-bills.show', $billB->id))->assertNotFound();
    }

    public function test_staff_without_permission_cannot_create_bills(): void
    {
        $tenant = $this->setUpTenant();
        $staff  = $this->makeUser($tenant, 'staff');
        $vendor = Vendor::create(['tenant_id' => $tenant->id, 'name' => 'Acme Supplies']);

        $this->actingAs($staff)->post(route('tenant.vendor-bills.store'), $this->payload($vendor))
            ->assertForbidden();
    }
}
