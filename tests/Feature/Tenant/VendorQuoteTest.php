<?php

namespace Tests\Feature\Tenant;

use App\Models\PurchaseOrder;
use App\Models\Tenant;
use App\Models\Vendor;
use App\Models\VendorQuote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class VendorQuoteTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function makeVendor(Tenant $tenant, string $name): Vendor
    {
        return Vendor::create(['tenant_id' => $tenant->id, 'name' => $name]);
    }

    private function draftPurchaseOrder(Tenant $tenant, int $createdBy): PurchaseOrder
    {
        return PurchaseOrder::factory()->create([
            'tenant_id'  => $tenant->id,
            'created_by' => $createdBy,
            'vendor_id'  => null,
            'status'     => 'draft',
            'items'      => [[
                'product_id'         => null,
                'name'               => 'Office Chairs',
                'description'        => null,
                'quantity'           => 10,
                'rate'               => 0,
                'tax_percent'        => 0,
                'amount'             => 0,
                'received_quantity'  => 0,
            ]],
        ]);
    }

    public function test_two_vendor_quotes_can_be_added_and_compared(): void
    {
        $tenant  = $this->setUpTenant();
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $po      = $this->draftPurchaseOrder($tenant, $admin->id);
        $vendorA = $this->makeVendor($tenant, 'Vendor A');
        $vendorB = $this->makeVendor($tenant, 'Vendor B');

        $payload = fn (Vendor $vendor, float $rate) => [
            'vendor_id' => $vendor->id,
            'items' => [[
                'product_id'  => null,
                'name'        => 'Office Chairs',
                'quantity'    => 10,
                'rate'        => $rate,
                'tax_percent' => 18,
            ]],
        ];

        $this->actingAs($admin)->post(route('tenant.purchase-orders.vendor-quotes.store', $po->id), $payload($vendorA, 1800))
            ->assertRedirect(route('tenant.purchase-orders.show', $po->id));
        $this->actingAs($admin)->post(route('tenant.purchase-orders.vendor-quotes.store', $po->id), $payload($vendorB, 2000))
            ->assertRedirect(route('tenant.purchase-orders.show', $po->id));

        $this->assertSame(2, VendorQuote::where('purchase_order_id', $po->id)->count());

        $quoteA = VendorQuote::where('vendor_id', $vendorA->id)->first();
        $this->assertEqualsWithDelta(21240.0, (float) $quoteA->total, 0.01); // 10*1800*1.18
    }

    public function test_selecting_a_quote_applies_its_vendor_and_rates_to_the_po(): void
    {
        $tenant  = $this->setUpTenant();
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $po      = $this->draftPurchaseOrder($tenant, $admin->id);
        $vendorA = $this->makeVendor($tenant, 'Vendor A');
        $vendorB = $this->makeVendor($tenant, 'Vendor B');

        $this->actingAs($admin)->post(route('tenant.purchase-orders.vendor-quotes.store', $po->id), [
            'vendor_id' => $vendorA->id,
            'items' => [['name' => 'Office Chairs', 'quantity' => 10, 'rate' => 1800, 'tax_percent' => 18]],
        ]);
        $this->actingAs($admin)->post(route('tenant.purchase-orders.vendor-quotes.store', $po->id), [
            'vendor_id' => $vendorB->id,
            'items' => [['name' => 'Office Chairs', 'quantity' => 10, 'rate' => 2000, 'tax_percent' => 18]],
        ]);

        $winningQuote = VendorQuote::where('vendor_id', $vendorA->id)->first();

        $this->actingAs($admin)->post(route('tenant.purchase-orders.vendor-quotes.select', [$po->id, $winningQuote->id]))
            ->assertRedirect(route('tenant.purchase-orders.show', $po->id));

        $po->refresh();
        $this->assertSame($vendorA->id, $po->vendor_id);
        $this->assertEqualsWithDelta(21240.0, (float) $po->total, 0.01);
        $this->assertEqualsWithDelta(1800.0, (float) $po->items[0]['rate'], 0.01);

        $this->assertSame('selected', $winningQuote->fresh()->status);
        $this->assertSame('rejected', VendorQuote::where('vendor_id', $vendorB->id)->first()->status);
    }

    public function test_a_quote_cannot_be_selected_once_the_po_is_no_longer_draft(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $po     = $this->draftPurchaseOrder($tenant, $admin->id);
        $vendor = $this->makeVendor($tenant, 'Vendor A');

        $this->actingAs($admin)->post(route('tenant.purchase-orders.vendor-quotes.store', $po->id), [
            'vendor_id' => $vendor->id,
            'items' => [['name' => 'Office Chairs', 'quantity' => 10, 'rate' => 1800, 'tax_percent' => 18]],
        ]);
        $quote = VendorQuote::first();

        // PO moves on (e.g. vendor assigned manually, sent) before the quote is selected.
        $po->update(['status' => 'sent', 'vendor_id' => $vendor->id]);

        $response = $this->actingAs($admin)->post(route('tenant.purchase-orders.vendor-quotes.select', [$po->id, $quote->id]));
        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertSame('submitted', $quote->fresh()->status);
    }

    public function test_vendor_quote_from_another_tenant_purchase_order_is_not_accessible(): void
    {
        $tenantA = $this->setUpTenant();
        $adminA  = $this->makeUser($tenantA, 'tenant_admin');

        $tenantB = Tenant::factory()->create();
        $poB     = $this->draftPurchaseOrder($tenantB, $adminA->id);

        $response = $this->actingAs($adminA)->post(route('tenant.purchase-orders.vendor-quotes.store', $poB->id), [
            'vendor_id' => $this->makeVendor($tenantB, 'Vendor X')->id,
            'items' => [['name' => 'X', 'quantity' => 1, 'rate' => 10]],
        ]);

        $response->assertNotFound();
    }
}
