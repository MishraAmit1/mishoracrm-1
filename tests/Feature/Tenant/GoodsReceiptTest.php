<?php

namespace Tests\Feature\Tenant;

use App\Models\GoodsReceiptNote;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class GoodsReceiptTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function sentPo($tenant, $admin, Product $product, float $qty = 20, float $rate = 100): PurchaseOrder
    {
        $vendor = Vendor::create(['tenant_id' => $tenant->id, 'name' => 'Acme Supplies']);

        return PurchaseOrder::factory()->create([
            'tenant_id'  => $tenant->id,
            'created_by' => $admin->id,
            'vendor_id'  => $vendor->id,
            'status'     => 'sent',
            'items'      => [[
                'name'              => $product->name,
                'product_id'        => $product->id,
                'quantity'          => $qty,
                'rate'              => $rate,
                'tax_percent'       => 18,
                'amount'            => $qty * $rate,
                'received_quantity' => 0,
            ]],
        ]);
    }

    public function test_only_accepted_quantity_is_credited_to_stock(): void
    {
        $tenant  = $this->setUpTenant();
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $product = Product::create([
            'tenant_id' => $tenant->id, 'name' => 'Steel Rod', 'type' => 'raw_material',
            'rate' => 120, 'cost_price' => 0, 'tax_percent' => 18, 'current_stock' => 0,
        ]);
        $po = $this->sentPo($tenant, $admin, $product, 20, 90);

        $this->actingAs($admin)->post(route('tenant.purchase-orders.receive', $po->id), [
            'items' => [[
                'received_quantity' => 20,
                'accepted_quantity' => 15,
                'rejection_reason'  => 'Bent — 5 units',
            ]],
        ])->assertRedirect();

        $this->assertEqualsWithDelta(15.0, (float) $product->fresh()->current_stock, 0.01);
        // Cost rolled from the accepted 15 units only, at the PO rate.
        $this->assertEqualsWithDelta(90.0, (float) $product->fresh()->cost_price, 0.01);

        $po->refresh();
        $this->assertEqualsWithDelta(15.0, (float) $po->items[0]['received_quantity'], 0.01);
        $this->assertEqualsWithDelta(5.0, (float) $po->items[0]['rejected_quantity'], 0.01);
        $this->assertSame('partially_received', $po->status);
    }

    public function test_a_grn_is_created_with_a_line_snapshot(): void
    {
        $tenant  = $this->setUpTenant();
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $product = Product::create([
            'tenant_id' => $tenant->id, 'name' => 'Steel Rod', 'type' => 'raw_material',
            'rate' => 120, 'tax_percent' => 18, 'current_stock' => 0,
        ]);
        $po = $this->sentPo($tenant, $admin, $product, 10, 50);

        $this->actingAs($admin)->post(route('tenant.purchase-orders.receive', $po->id), [
            'items' => [['received_quantity' => 10]],
            'note'  => 'Challan #55',
        ]);

        $grn = GoodsReceiptNote::first();
        $this->assertNotNull($grn);
        $this->assertSame($po->id, $grn->purchase_order_id);
        $this->assertSame('Challan #55', $grn->note);
        $this->assertEqualsWithDelta(10.0, $grn->totalAccepted(), 0.01);
        $this->assertEqualsWithDelta(0.0, $grn->totalRejected(), 0.01);
        $this->assertSame('received', $po->fresh()->status);
    }

    public function test_multiple_grns_accumulate_and_never_exceed_the_ordered_quantity(): void
    {
        $tenant  = $this->setUpTenant();
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $product = Product::create([
            'tenant_id' => $tenant->id, 'name' => 'Steel Rod', 'type' => 'raw_material',
            'rate' => 120, 'tax_percent' => 18, 'current_stock' => 0,
        ]);
        $po = $this->sentPo($tenant, $admin, $product, 10, 50);

        $this->actingAs($admin)->post(route('tenant.purchase-orders.receive', $po->id), [
            'items' => [['received_quantity' => 6]],
        ]);
        $this->assertSame('partially_received', $po->fresh()->status);

        // Second delivery says 10 arrived but only 4 slots remain.
        $this->actingAs($admin)->post(route('tenant.purchase-orders.receive', $po->id), [
            'items' => [['received_quantity' => 10]],
        ]);

        $po->refresh();
        $this->assertEqualsWithDelta(10.0, (float) $po->items[0]['received_quantity'], 0.01);
        $this->assertEqualsWithDelta(10.0, (float) $product->fresh()->current_stock, 0.01);
        $this->assertSame('received', $po->status);
        $this->assertSame(2, GoodsReceiptNote::where('purchase_order_id', $po->id)->count());
    }

    public function test_receive_still_blocked_on_a_draft_po(): void
    {
        $tenant  = $this->setUpTenant();
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $po      = PurchaseOrder::factory()->create(['tenant_id' => $tenant->id, 'created_by' => $admin->id, 'status' => 'draft']);

        $this->actingAs($admin)->post(route('tenant.purchase-orders.receive', $po->id), [
            'items' => [['received_quantity' => 1]],
        ])->assertForbidden();
    }
}
