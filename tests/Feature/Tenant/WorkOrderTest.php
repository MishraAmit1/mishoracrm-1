<?php

namespace Tests\Feature\Tenant;

use App\Models\BillOfMaterialItem;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class WorkOrderTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function buildFinishedGoodWithBom(Tenant $tenant, float $materialStock = 100): array
    {
        $finishedGood = Product::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Steel Chair',
            'type'      => 'finished_good',
            'rate'      => 1500,
            'tax_percent' => 18,
            'current_stock' => 0,
        ]);

        $rod = Product::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Steel Rod',
            'type'      => 'raw_material',
            'rate'      => 50,
            'tax_percent' => 18,
            'current_stock' => $materialStock,
        ]);

        BillOfMaterialItem::create([
            'tenant_id'         => $tenant->id,
            'product_id'        => $finishedGood->id,
            'material_id'       => $rod->id,
            'quantity_per_unit' => 5,
        ]);

        return [$finishedGood, $rod];
    }

    public function test_create_start_complete_deducts_raw_material_and_credits_finished_good(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        [$finishedGood, $rod] = $this->buildFinishedGoodWithBom($tenant, materialStock: 100);

        $this->actingAs($admin)->post(route('tenant.work-orders.store'), [
            'product_id' => $finishedGood->id,
            'quantity'   => 10,
        ])->assertRedirect();

        $workOrder = WorkOrder::first();
        $this->assertNotNull($workOrder);
        $this->assertSame('pending', $workOrder->status);

        $this->actingAs($admin)->post(route('tenant.work-orders.start', $workOrder->id))
            ->assertRedirect(route('tenant.work-orders.show', $workOrder->id));
        $this->assertSame('in_progress', $workOrder->fresh()->status);

        $this->actingAs($admin)->post(route('tenant.work-orders.complete', $workOrder->id))
            ->assertRedirect(route('tenant.work-orders.show', $workOrder->id));

        $workOrder->refresh();
        $this->assertSame('completed', $workOrder->status);
        $this->assertNotNull($workOrder->completed_at);

        // 10 units need 10*5=50 rods; 100 - 50 = 50 remaining.
        $this->assertEqualsWithDelta(50.0, (float) $rod->fresh()->current_stock, 0.01);
        // Finished good credited by the produced quantity.
        $this->assertEqualsWithDelta(10.0, (float) $finishedGood->fresh()->current_stock, 0.01);
    }

    public function test_complete_is_blocked_when_raw_material_is_short(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        [$finishedGood, $rod] = $this->buildFinishedGoodWithBom($tenant, materialStock: 10);

        // Needs 10*5=50 rods, only 10 in stock.
        $this->actingAs($admin)->post(route('tenant.work-orders.store'), [
            'product_id' => $finishedGood->id,
            'quantity'   => 10,
        ]);
        $workOrder = WorkOrder::first();
        $this->actingAs($admin)->post(route('tenant.work-orders.start', $workOrder->id));

        $response = $this->actingAs($admin)->post(route('tenant.work-orders.complete', $workOrder->id));
        $response->assertStatus(302);
        $response->assertSessionHas('error');

        $workOrder->refresh();
        $this->assertSame('in_progress', $workOrder->status);
        $this->assertNull($workOrder->completed_at);

        // Stock untouched since completion was rejected.
        $this->assertEqualsWithDelta(10.0, (float) $rod->fresh()->current_stock, 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $finishedGood->fresh()->current_stock, 0.01);
    }

    public function test_cancel_leaves_stock_untouched(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        [$finishedGood, $rod] = $this->buildFinishedGoodWithBom($tenant, materialStock: 100);

        $this->actingAs($admin)->post(route('tenant.work-orders.store'), [
            'product_id' => $finishedGood->id,
            'quantity'   => 10,
        ]);
        $workOrder = WorkOrder::first();
        $this->actingAs($admin)->post(route('tenant.work-orders.start', $workOrder->id));

        $this->actingAs($admin)->post(route('tenant.work-orders.cancel', $workOrder->id))
            ->assertRedirect(route('tenant.work-orders.show', $workOrder->id));

        $this->assertSame('cancelled', $workOrder->fresh()->status);
        $this->assertEqualsWithDelta(100.0, (float) $rod->fresh()->current_stock, 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $finishedGood->fresh()->current_stock, 0.01);
    }

    public function test_a_work_order_from_another_tenant_is_not_accessible(): void
    {
        $tenantA = $this->setUpTenant();
        $adminA  = $this->makeUser($tenantA, 'tenant_admin');

        $tenantB = Tenant::factory()->create();
        [$finishedGoodB] = $this->buildFinishedGoodWithBom($tenantB);
        $workOrderB = WorkOrder::create([
            'tenant_id'  => $tenantB->id,
            'product_id' => $finishedGoodB->id,
            'number'     => 'WO-20260101-0001',
            'quantity'   => 5,
            'status'     => 'pending',
            'created_by' => $adminA->id,
        ]);

        $this->actingAs($adminA)->get(route('tenant.work-orders.show', $workOrderB->id))
            ->assertNotFound();
    }
}
