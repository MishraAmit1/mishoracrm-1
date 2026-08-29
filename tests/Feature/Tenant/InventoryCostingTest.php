<?php

namespace Tests\Feature\Tenant;

use App\Models\BillOfMaterialItem;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use App\Models\WorkOrder;
use App\Services\StockService;
use App\Services\WorkOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class InventoryCostingTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function rawMaterial(int $tenantId, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'tenant_id'     => $tenantId,
            'name'          => 'Steel Rod',
            'type'          => 'raw_material',
            'rate'          => 100,
            'cost_price'    => 50,
            'tax_percent'   => 18,
            'current_stock' => 10,
        ], $overrides));
    }

    public function test_receiving_stock_at_a_known_cost_rolls_the_weighted_average_forward(): void
    {
        $tenant  = $this->setUpTenant();
        $product = $this->rawMaterial($tenant->id); // 10 @ 50

        // Receive 10 more at 70 → (10*50 + 10*70) / 20 = 60
        $batch = StockService::receiveBatch($product, 10, null, null, null, null, 70);

        $this->assertEqualsWithDelta(60.0, (float) $product->fresh()->cost_price, 0.01);
        $this->assertEqualsWithDelta(70.0, (float) $batch->unit_cost, 0.01);
        $this->assertEqualsWithDelta(20.0, (float) $product->fresh()->current_stock, 0.01);
    }

    public function test_receiving_with_no_cost_leaves_the_average_untouched_and_stamps_current_cost_on_the_batch(): void
    {
        $tenant  = $this->setUpTenant();
        $product = $this->rawMaterial($tenant->id); // 10 @ 50

        $batch = StockService::receiveBatch($product, 5, null, null); // no unit cost

        $this->assertEqualsWithDelta(50.0, (float) $product->fresh()->cost_price, 0.01);
        $this->assertEqualsWithDelta(50.0, (float) $batch->unit_cost, 0.01);
    }

    public function test_purchase_order_receive_updates_the_material_cost_from_the_line_rate(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $vendor = Vendor::create(['tenant_id' => $tenant->id, 'name' => 'Acme Supplies']);
        $rod    = $this->rawMaterial($tenant->id, ['current_stock' => 0, 'cost_price' => 40]);

        $po = PurchaseOrder::factory()->create([
            'tenant_id'  => $tenant->id,
            'created_by' => $admin->id,
            'vendor_id'  => $vendor->id,
            'status'     => 'sent',
            'items'      => [[
                'name'              => 'Steel Rod',
                'product_id'        => $rod->id,
                'quantity'          => 20,
                'rate'              => 90,
                'tax_percent'       => 18,
                'amount'            => 1800,
                'received_quantity' => 0,
            ]],
        ]);

        $this->actingAs($admin)->post(route('tenant.purchase-orders.receive', $po->id), [
            'items' => [
                ['received_quantity' => 20, 'batch_number' => 'ROD-PO-1'],
            ],
        ])->assertRedirect();

        // Stock was 0, so the average becomes the incoming rate exactly.
        $this->assertEqualsWithDelta(90.0, (float) $rod->fresh()->cost_price, 0.01);
        $this->assertEqualsWithDelta(90.0, (float) ProductBatch::where('batch_number', 'ROD-PO-1')->first()->unit_cost, 0.01);
    }

    public function test_bill_of_materials_costing_uses_cost_price_not_selling_rate(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $chair = Product::create([
            'tenant_id' => $tenant->id, 'name' => 'Steel Chair', 'type' => 'finished_good',
            'rate' => 1500, 'cost_price' => 1500, 'tax_percent' => 18, 'current_stock' => 0,
        ]);
        $rod = $this->rawMaterial($tenant->id, ['current_stock' => 100, 'rate' => 100, 'cost_price' => 60]);

        BillOfMaterialItem::create([
            'tenant_id' => $tenant->id, 'product_id' => $chair->id,
            'material_id' => $rod->id, 'quantity_per_unit' => 5,
        ]);

        $wo = WorkOrder::create([
            'tenant_id' => $tenant->id, 'product_id' => $chair->id,
            'number' => 'WO-COST-1', 'quantity' => 10, 'status' => 'pending',
            'created_by' => $admin->id,
        ]);

        // 5 rods/unit * 10 units * 60 cost = 3000 (not 100 selling rate → 5000)
        $this->assertEqualsWithDelta(3000.0, $wo->calculateLiveMaterialCost(), 0.01);
    }

    public function test_completing_a_work_order_sets_the_finished_goods_cost_price(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $chair = Product::create([
            'tenant_id' => $tenant->id, 'name' => 'Steel Chair', 'type' => 'finished_good',
            'rate' => 1500, 'cost_price' => 1500, 'tax_percent' => 18, 'current_stock' => 0,
        ]);
        $rod = $this->rawMaterial($tenant->id, ['current_stock' => 0, 'cost_price' => 60]);
        StockService::receiveBatch($rod, 100, 'ROD-1', null, null, null, 60);

        BillOfMaterialItem::create([
            'tenant_id' => $tenant->id, 'product_id' => $chair->id,
            'material_id' => $rod->id, 'quantity_per_unit' => 5,
        ]);

        $wo = WorkOrder::create([
            'tenant_id' => $tenant->id, 'product_id' => $chair->id,
            'number' => 'WO-COST-2', 'quantity' => 10, 'status' => 'in_progress',
            'created_by' => $admin->id, 'started_at' => now(),
            'labor_cost' => 500, 'machine_cost' => 200,
        ]);

        WorkOrderService::complete($wo);

        // material 3000 + labor 500 + machine 200 = 3700 over 10 units = 370
        $this->assertEqualsWithDelta(370.0, (float) $chair->fresh()->cost_price, 0.01);

        $fgBatch = ProductBatch::where('product_id', $chair->id)->first();
        $this->assertEqualsWithDelta(370.0, (float) $fgBatch->unit_cost, 0.01);
    }

    public function test_inventory_value_helper_uses_the_cost_basis(): void
    {
        $tenant  = $this->setUpTenant();
        $product = $this->rawMaterial($tenant->id, ['current_stock' => 8, 'cost_price' => 25]);

        $this->assertEqualsWithDelta(200.0, $product->inventoryValue(), 0.01);
    }
}
