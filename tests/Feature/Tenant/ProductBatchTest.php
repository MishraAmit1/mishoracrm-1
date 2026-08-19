<?php

namespace Tests\Feature\Tenant;

use App\Models\BillOfMaterialItem;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Tenant;
use App\Models\WorkOrder;
use App\Services\StockService;
use App\Services\WorkOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class ProductBatchTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    public function test_receiving_creates_a_batch_and_keeps_aggregate_stock_in_sync(): void
    {
        $tenant = $this->setUpTenant();
        $product = Product::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Steel Rod',
            'type'      => 'raw_material',
            'rate'      => 50,
            'tax_percent' => 18,
            'current_stock' => 0,
        ]);

        $batch = StockService::receiveBatch($product, 40, 'ROD-BATCH-1', '2027-01-01');

        $this->assertSame(40.0, (float) $batch->quantity);
        $this->assertSame('ROD-BATCH-1', $batch->batch_number);
        $this->assertEqualsWithDelta(40.0, (float) $product->fresh()->current_stock, 0.01);
    }

    public function test_fefo_consumes_soonest_expiry_batch_first_across_multiple_batches(): void
    {
        $tenant = $this->setUpTenant();
        $product = Product::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Steel Rod',
            'type'      => 'raw_material',
            'rate'      => 50,
            'tax_percent' => 18,
            'current_stock' => 0,
        ]);

        StockService::receiveBatch($product, 20, 'LATE', '2027-06-01');
        StockService::receiveBatch($product, 15, 'SOON', '2027-01-01');
        StockService::receiveBatch($product, 10, 'NOEXP', null);

        // Consume 25 — should fully drain SOON (15) then take 10 from LATE.
        $consumed = StockService::consumeBatchesFEFO($product, 25);

        $this->assertCount(2, $consumed);
        $this->assertSame('SOON', $consumed[0]['batch_number']);
        $this->assertEqualsWithDelta(15.0, $consumed[0]['consumed_qty'], 0.01);
        $this->assertSame('LATE', $consumed[1]['batch_number']);
        $this->assertEqualsWithDelta(10.0, $consumed[1]['consumed_qty'], 0.01);

        $this->assertEqualsWithDelta(0.0, (float) ProductBatch::where('batch_number', 'SOON')->first()->quantity, 0.01);
        $this->assertEqualsWithDelta(10.0, (float) ProductBatch::where('batch_number', 'LATE')->first()->quantity, 0.01);
        $this->assertEqualsWithDelta(10.0, (float) ProductBatch::where('batch_number', 'NOEXP')->first()->quantity, 0.01);
        $this->assertEqualsWithDelta(20.0, (float) $product->fresh()->current_stock, 0.01);
    }

    public function test_a_sale_that_spans_two_batches_splits_correctly(): void
    {
        $tenant  = $this->setUpTenant();
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = Contact::create(['tenant_id' => $tenant->id, 'name' => 'Acme Corp', 'phone' => '9999999999']);
        $product = Product::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Steel Chair',
            'type'      => 'finished_good',
            'rate'      => 1000,
            'tax_percent' => 18,
            'current_stock' => 0,
        ]);

        StockService::receiveBatch($product, 5, 'B1', '2027-01-01');
        StockService::receiveBatch($product, 5, 'B2', '2027-02-01');

        $this->actingAs($admin)->post(route('tenant.invoices.store'), [
            'contact_id' => $contact->id,
            'date'       => now()->toDateString(),
            'due_date'   => now()->addDays(7)->toDateString(),
            'items'      => [[
                'description' => 'Steel Chair',
                'product_id'  => $product->id,
                'quantity'    => 8,
                'rate'        => 1000,
            ]],
        ]);

        $this->assertEqualsWithDelta(2.0, (float) $product->fresh()->current_stock, 0.01);
        $this->assertEqualsWithDelta(0.0, (float) ProductBatch::where('batch_number', 'B1')->first()->quantity, 0.01);
        $this->assertEqualsWithDelta(2.0, (float) ProductBatch::where('batch_number', 'B2')->first()->quantity, 0.01);
    }

    public function test_work_order_completion_creates_finished_good_batch_and_consumes_raw_material_batches_fefo(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $finishedGood = Product::create([
            'tenant_id' => $tenant->id, 'name' => 'Steel Chair', 'type' => 'finished_good',
            'rate' => 1500, 'tax_percent' => 18, 'current_stock' => 0,
        ]);
        $rod = Product::create([
            'tenant_id' => $tenant->id, 'name' => 'Steel Rod', 'type' => 'raw_material',
            'rate' => 50, 'tax_percent' => 18, 'current_stock' => 0,
        ]);
        BillOfMaterialItem::create([
            'tenant_id' => $tenant->id, 'product_id' => $finishedGood->id,
            'material_id' => $rod->id, 'quantity_per_unit' => 5,
        ]);

        StockService::receiveBatch($rod, 100, 'ROD-1', null);

        $workOrder = WorkOrder::create([
            'tenant_id' => $tenant->id, 'product_id' => $finishedGood->id,
            'number' => 'WO-20260101-0001', 'quantity' => 10, 'status' => 'in_progress',
            'created_by' => $admin->id, 'started_at' => now(),
        ]);

        WorkOrderService::complete($workOrder, '2027-12-31');

        $this->assertEqualsWithDelta(50.0, (float) $rod->fresh()->current_stock, 0.01);
        $this->assertEqualsWithDelta(10.0, (float) $finishedGood->fresh()->current_stock, 0.01);

        $fgBatch = ProductBatch::where('product_id', $finishedGood->id)->first();
        $this->assertNotNull($fgBatch);
        $this->assertEqualsWithDelta(10.0, (float) $fgBatch->quantity, 0.01);
        $this->assertSame($workOrder->id, $fgBatch->work_order_id);
        $this->assertSame('2027-12-31', $fgBatch->expiry_date->toDateString());
    }

    public function test_expiry_alert_command_fires_only_for_batches_inside_the_window(): void
    {
        $tenant = $this->setUpTenant();
        $this->makeUser($tenant, 'tenant_admin');
        $product = Product::create([
            'tenant_id' => $tenant->id, 'name' => 'Steel Rod', 'type' => 'raw_material',
            'rate' => 50, 'tax_percent' => 18, 'current_stock' => 0,
        ]);

        StockService::receiveBatch($product, 10, 'EXPIRING-SOON', now()->addDays(3)->toDateString());
        StockService::receiveBatch($product, 10, 'EXPIRING-LATER', now()->addDays(30)->toDateString());

        $this->artisan('products:check-batch-expiry', ['--days' => 7])->assertSuccessful();

        $this->assertDatabaseHas('notifications', ['type' => 'product.batch_expiring']);
        $this->assertNotNull(ProductBatch::where('batch_number', 'EXPIRING-SOON')->first()->expiry_notified_at);
        $this->assertNull(ProductBatch::where('batch_number', 'EXPIRING-LATER')->first()->expiry_notified_at);
    }

    public function test_tenant_isolation_on_batches(): void
    {
        $tenantA = $this->setUpTenant();
        $tenantB = Tenant::factory()->create();

        $productA = Product::create([
            'tenant_id' => $tenantA->id, 'name' => 'A Product', 'type' => 'raw_material',
            'rate' => 10, 'tax_percent' => 18, 'current_stock' => 0,
        ]);
        $productB = Product::create([
            'tenant_id' => $tenantB->id, 'name' => 'B Product', 'type' => 'raw_material',
            'rate' => 10, 'tax_percent' => 18, 'current_stock' => 0,
        ]);

        StockService::receiveBatch($productA, 10, 'A-BATCH', null);
        StockService::receiveBatch($productB, 10, 'B-BATCH', null);

        $this->assertSame($tenantA->id, ProductBatch::where('batch_number', 'A-BATCH')->first()->tenant_id);
        $this->assertSame($tenantB->id, ProductBatch::where('batch_number', 'B-BATCH')->first()->tenant_id);
    }
}
