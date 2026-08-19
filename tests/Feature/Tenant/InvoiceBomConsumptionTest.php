<?php

namespace Tests\Feature\Tenant;

use App\Models\BillOfMaterialItem;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class InvoiceBomConsumptionTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function buildShampooWithBom(Tenant $tenant): array
    {
        $shampoo = Product::create([
            'tenant_id'     => $tenant->id,
            'name'          => '5ml Shampoo',
            'type'          => 'finished_good',
            'rate'          => 50,
            'tax_percent'   => 18,
            'current_stock' => 0,
        ]);

        $bottle = Product::create([
            'tenant_id'     => $tenant->id,
            'name'          => 'Bottle',
            'type'          => 'raw_material',
            'rate'          => 5,
            'tax_percent'   => 18,
            'current_stock' => 100,
        ]);

        $base = Product::create([
            'tenant_id'     => $tenant->id,
            'name'          => 'Shampoo Base',
            'type'          => 'raw_material',
            'rate'          => 2,
            'tax_percent'   => 18,
            'current_stock' => 50,
        ]);

        BillOfMaterialItem::create([
            'tenant_id'         => $tenant->id,
            'product_id'        => $shampoo->id,
            'material_id'       => $bottle->id,
            'quantity_per_unit' => 1,
        ]);
        BillOfMaterialItem::create([
            'tenant_id'         => $tenant->id,
            'product_id'        => $shampoo->id,
            'material_id'       => $base->id,
            'quantity_per_unit' => 5, // 5ml of base per bottle
        ]);

        return [$shampoo, $bottle, $base];
    }

    private function invoicePayload(Contact $contact, Product $product, float $qty): array
    {
        return [
            'contact_id' => $contact->id,
            'date'       => now()->toDateString(),
            'due_date'   => now()->addDays(7)->toDateString(),
            'items'      => [[
                'description' => $product->name,
                'product_id'  => $product->id,
                'quantity'    => $qty,
                'rate'        => 50,
            ]],
        ];
    }

    public function test_selling_a_finished_good_auto_deducts_its_bom_raw_materials_without_a_work_order(): void
    {
        $tenant  = $this->setUpTenant();
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = Contact::create(['tenant_id' => $tenant->id, 'name' => 'Walk-in Customer', 'phone' => '9999999999']);
        [$shampoo, $bottle, $base] = $this->buildShampooWithBom($tenant);

        // Sell 10 bottles of shampoo directly — no Work Order involved.
        $this->actingAs($admin)->post(route('tenant.invoices.store'), $this->invoicePayload($contact, $shampoo, 10));

        $invoice = Invoice::first();
        $this->assertNotNull($invoice);

        // 10 bottles used, 10*5=50 base used.
        $this->assertEqualsWithDelta(90.0, (float) $bottle->fresh()->current_stock, 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $base->fresh()->current_stock, 0.01);
    }

    public function test_deleting_the_invoice_restores_the_bom_raw_materials(): void
    {
        $tenant  = $this->setUpTenant();
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = Contact::create(['tenant_id' => $tenant->id, 'name' => 'Walk-in Customer', 'phone' => '9999999999']);
        [$shampoo, $bottle, $base] = $this->buildShampooWithBom($tenant);

        $this->actingAs($admin)->post(route('tenant.invoices.store'), $this->invoicePayload($contact, $shampoo, 10));
        $invoice = Invoice::first();

        $this->actingAs($admin)->delete(route('tenant.invoices.destroy', $invoice->id));

        $this->assertEqualsWithDelta(100.0, (float) $bottle->fresh()->current_stock, 0.01);
        $this->assertEqualsWithDelta(50.0, (float) $base->fresh()->current_stock, 0.01);
    }

    public function test_editing_invoice_quantity_down_adjusts_bom_materials_by_the_delta_only(): void
    {
        $tenant  = $this->setUpTenant();
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = Contact::create(['tenant_id' => $tenant->id, 'name' => 'Walk-in Customer', 'phone' => '9999999999']);
        [$shampoo, $bottle, $base] = $this->buildShampooWithBom($tenant);

        $this->actingAs($admin)->post(route('tenant.invoices.store'), $this->invoicePayload($contact, $shampoo, 10));
        $invoice = Invoice::first();
        $this->assertEqualsWithDelta(90.0, (float) $bottle->fresh()->current_stock, 0.01);

        // Drop quantity from 10 to 4 — should restore 6 bottles / 30 base.
        $this->actingAs($admin)->put(
            route('tenant.invoices.update', $invoice->id),
            $this->invoicePayload($contact, $shampoo, 4)
        );

        $this->assertEqualsWithDelta(96.0, (float) $bottle->fresh()->current_stock, 0.01);
        $this->assertEqualsWithDelta(30.0, (float) $base->fresh()->current_stock, 0.01);
    }

    public function test_a_raw_material_low_stock_alert_fires_when_bom_consumption_crosses_the_reorder_line(): void
    {
        $tenant  = $this->setUpTenant();
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = Contact::create(['tenant_id' => $tenant->id, 'name' => 'Walk-in Customer', 'phone' => '9999999999']);
        [$shampoo, $bottle, $base] = $this->buildShampooWithBom($tenant);
        $bottle->update(['reorder_level' => 95]); // 100 in stock, selling 10 will drop it to 90 — crosses 95.

        $this->actingAs($admin)->post(route('tenant.invoices.store'), $this->invoicePayload($contact, $shampoo, 10));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'type'    => 'product.low_stock',
        ]);
    }

    public function test_a_product_with_no_bom_is_unaffected(): void
    {
        $tenant  = $this->setUpTenant();
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = Contact::create(['tenant_id' => $tenant->id, 'name' => 'Walk-in Customer', 'phone' => '9999999999']);
        $plainProduct = Product::create([
            'tenant_id' => $tenant->id, 'name' => 'Plain Item', 'type' => 'finished_good',
            'rate' => 100, 'tax_percent' => 18, 'current_stock' => 20,
        ]);

        $this->actingAs($admin)->post(route('tenant.invoices.store'), $this->invoicePayload($contact, $plainProduct, 3));

        $this->assertEqualsWithDelta(17.0, (float) $plainProduct->fresh()->current_stock, 0.01);
    }
}
