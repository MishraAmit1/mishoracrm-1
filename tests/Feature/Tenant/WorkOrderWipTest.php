<?php

namespace Tests\Feature\Tenant;

use App\Models\BillOfMaterialItem;
use App\Models\Product;
use App\Models\WorkOrder;
use App\Services\StockService;
use App\Services\WorkOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class WorkOrderWipTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    /** @return array{0: Product, 1: Product} [finishedGood, rawMaterial] */
    private function chairWithBom($tenant, float $rodStock = 100, float $perUnit = 5): array
    {
        $chair = Product::create([
            'tenant_id' => $tenant->id, 'name' => 'Steel Chair', 'type' => 'finished_good',
            'rate' => 1500, 'cost_price' => 1500, 'tax_percent' => 18, 'current_stock' => 0,
        ]);
        $rod = Product::create([
            'tenant_id' => $tenant->id, 'name' => 'Steel Rod', 'type' => 'raw_material',
            'rate' => 100, 'cost_price' => 60, 'tax_percent' => 18, 'current_stock' => 0,
        ]);
        StockService::receiveBatch($rod, $rodStock, 'ROD-1', null, null, null, 60);

        BillOfMaterialItem::create([
            'tenant_id' => $tenant->id, 'product_id' => $chair->id,
            'material_id' => $rod->id, 'quantity_per_unit' => $perUnit,
        ]);

        return [$chair, $rod];
    }

    private function makeWo($tenant, $admin, Product $chair, float $qty): WorkOrder
    {
        $this->actingAs($admin);

        return WorkOrderService::store([
            'product_id' => $chair->id,
            'quantity'   => $qty,
        ], $tenant->id, $admin->id);
    }

    public function test_creating_a_work_order_reserves_bom_materials_without_moving_physical_stock(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        [$chair, $rod] = $this->chairWithBom($tenant, 100, 5);

        $this->makeWo($tenant, $admin, $chair, 10); // needs 50 rods

        $rod->refresh();
        $this->assertEqualsWithDelta(100.0, (float) $rod->current_stock, 0.01);
        $this->assertEqualsWithDelta(50.0, (float) $rod->reserved_stock, 0.01);
        $this->assertEqualsWithDelta(50.0, $rod->availableStock(), 0.01);
    }

    public function test_a_second_work_order_sees_reduced_availability(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        [$chair, $rod] = $this->chairWithBom($tenant, 100, 5);

        $this->makeWo($tenant, $admin, $chair, 10); // reserves 50
        $this->makeWo($tenant, $admin, $chair, 6);  // reserves another 30

        $this->assertEqualsWithDelta(80.0, (float) $rod->fresh()->reserved_stock, 0.01);
        $this->assertEqualsWithDelta(20.0, $rod->fresh()->availableStock(), 0.01);
    }

    public function test_starting_a_work_order_issues_materials_and_freezes_cost(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        [$chair, $rod] = $this->chairWithBom($tenant, 100, 5);
        $wo = $this->makeWo($tenant, $admin, $chair, 10);

        WorkOrderService::start($wo);
        $wo->refresh();
        $rod->refresh();

        $this->assertSame('in_progress', $wo->status);
        $this->assertNotNull($wo->materials_issued_at);
        $this->assertEqualsWithDelta(3000.0, (float) $wo->material_cost_snapshot, 0.01); // 50 * 60
        $this->assertEqualsWithDelta(50.0, (float) $rod->current_stock, 0.01); // consumed
        $this->assertEqualsWithDelta(0.0, (float) $rod->reserved_stock, 0.01); // reservation released
    }

    public function test_completing_after_issue_credits_finished_good_without_double_consuming(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        [$chair, $rod] = $this->chairWithBom($tenant, 100, 5);
        $wo = $this->makeWo($tenant, $admin, $chair, 10);

        WorkOrderService::start($wo);
        WorkOrderService::complete($wo->fresh());

        $rod->refresh();
        $chair->refresh();

        $this->assertEqualsWithDelta(50.0, (float) $rod->current_stock, 0.01);   // still 50, not 0
        $this->assertEqualsWithDelta(10.0, (float) $chair->current_stock, 0.01); // finished goods in
        $this->assertEqualsWithDelta(300.0, (float) $chair->cost_price, 0.01);   // 3000 / 10
        $this->assertSame('completed', $wo->fresh()->status);
    }

    public function test_cancelling_a_pending_work_order_releases_the_reservation(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        [$chair, $rod] = $this->chairWithBom($tenant, 100, 5);
        $wo = $this->makeWo($tenant, $admin, $chair, 10);

        WorkOrderService::cancel($wo);

        $this->assertEqualsWithDelta(0.0, (float) $rod->fresh()->reserved_stock, 0.01);
        $this->assertEqualsWithDelta(100.0, (float) $rod->fresh()->current_stock, 0.01);
    }

    public function test_cancelling_a_wip_work_order_returns_issued_materials_to_stock(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        [$chair, $rod] = $this->chairWithBom($tenant, 100, 5);
        $wo = $this->makeWo($tenant, $admin, $chair, 10);

        WorkOrderService::start($wo);
        $this->assertEqualsWithDelta(50.0, (float) $rod->fresh()->current_stock, 0.01);

        WorkOrderService::cancel($wo->fresh());

        $this->assertEqualsWithDelta(100.0, (float) $rod->fresh()->current_stock, 0.01);
        $this->assertSame('cancelled', $wo->fresh()->status);
    }

    public function test_start_is_blocked_when_physical_stock_is_short(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        [$chair, $rod] = $this->chairWithBom($tenant, 20, 5); // only 20 rods, need 50
        $wo = $this->makeWo($tenant, $admin, $chair, 10);

        $this->expectException(ValidationException::class);
        WorkOrderService::start($wo);
    }

    public function test_scrap_reduces_credited_stock_and_raises_cost_per_good_unit(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        [$chair, $rod] = $this->chairWithBom($tenant, 100, 5); // 10 units → 50 rods @ 60 = 3000
        $wo = $this->makeWo($tenant, $admin, $chair, 10);

        WorkOrderService::start($wo);
        // 8 good, 2 scrap
        WorkOrderService::complete($wo->fresh(), null, 8, 2, 'warping');

        $wo->refresh();
        $chair->refresh();

        $this->assertEqualsWithDelta(8.0, (float) $chair->current_stock, 0.01); // only good output
        $this->assertEqualsWithDelta(50.0, (float) $rod->fresh()->current_stock, 0.01); // full run consumed
        $this->assertEqualsWithDelta(8.0, (float) $wo->produced_quantity, 0.01);
        $this->assertEqualsWithDelta(2.0, (float) $wo->scrap_quantity, 0.01);
        // 3000 material / 8 good units = 375/unit (vs 300 with no scrap)
        $this->assertEqualsWithDelta(375.0, (float) $chair->cost_price, 0.01);
        $this->assertEqualsWithDelta(375.0, $wo->cost_per_unit, 0.01);
    }

    public function test_total_scrap_produces_nothing_but_still_completes(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        [$chair, $rod] = $this->chairWithBom($tenant, 100, 5);
        $wo = $this->makeWo($tenant, $admin, $chair, 10);

        WorkOrderService::start($wo);
        WorkOrderService::complete($wo->fresh(), null, 0, 10, 'batch contamination');

        $this->assertSame('completed', $wo->fresh()->status);
        $this->assertEqualsWithDelta(0.0, (float) $chair->fresh()->current_stock, 0.01);
        $this->assertEqualsWithDelta(50.0, (float) $rod->fresh()->current_stock, 0.01); // materials still gone
    }

    public function test_editing_a_pending_work_order_repoints_the_reservation(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        [$chair, $rod] = $this->chairWithBom($tenant, 100, 5);
        $wo = $this->makeWo($tenant, $admin, $chair, 10); // reserves 50

        WorkOrderService::update($wo, ['quantity' => 4]); // now needs 20

        $this->assertEqualsWithDelta(20.0, (float) $rod->fresh()->reserved_stock, 0.01);
    }
}
