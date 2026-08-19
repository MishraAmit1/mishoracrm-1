<?php

namespace Tests\Feature\Tenant;

use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class ProductStockTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function makeContact(Tenant $tenant): Contact
    {
        return Contact::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Acme Corp',
            'phone'     => '9999999999',
        ]);
    }

    private function invoicePayload(Contact $contact, array $itemOverrides = []): array
    {
        return [
            'contact_id' => $contact->id,
            'date'       => now()->toDateString(),
            'due_date'   => now()->addDays(7)->toDateString(),
            'items'      => [array_merge([
                'description' => 'Steel Chair',
                'quantity'    => 3,
                'rate'        => 1000,
            ], $itemOverrides)],
        ];
    }

    public function test_creating_an_invoice_with_a_product_linked_item_decrements_stock(): void
    {
        $tenant  = $this->setUpTenant();
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = $this->makeContact($tenant);
        $product = Product::create([
            'tenant_id'     => $tenant->id,
            'name'          => 'Steel Chair',
            'type'          => 'finished_good',
            'rate'          => 1000,
            'tax_percent'   => 18,
            'current_stock' => 20,
        ]);

        $response = $this->actingAs($admin)->post(route('tenant.invoices.store'), $this->invoicePayload($contact, [
            'product_id' => $product->id,
            'quantity'   => 3,
        ]));

        $invoice = Invoice::first();
        $this->assertNotNull($invoice);
        $response->assertRedirect(route('tenant.invoices.show', $invoice->id));

        $this->assertEqualsWithDelta(17.0, (float) $product->fresh()->current_stock, 0.01);
    }

    public function test_deleting_an_invoice_restores_stock(): void
    {
        $tenant  = $this->setUpTenant();
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = $this->makeContact($tenant);
        $product = Product::create([
            'tenant_id'     => $tenant->id,
            'name'          => 'Steel Chair',
            'type'          => 'finished_good',
            'rate'          => 1000,
            'tax_percent'   => 18,
            'current_stock' => 20,
        ]);

        $this->actingAs($admin)->post(route('tenant.invoices.store'), $this->invoicePayload($contact, [
            'product_id' => $product->id,
            'quantity'   => 3,
        ]));

        $invoice = Invoice::first();
        $this->assertEqualsWithDelta(17.0, (float) $product->fresh()->current_stock, 0.01);

        $this->actingAs($admin)->delete(route('tenant.invoices.destroy', $invoice->id))
            ->assertRedirect(route('tenant.invoices.index'));

        $this->assertEqualsWithDelta(20.0, (float) $product->fresh()->current_stock, 0.01);
    }

    public function test_editing_invoice_item_quantity_adjusts_the_delta_correctly_not_double_counted(): void
    {
        $tenant  = $this->setUpTenant();
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = $this->makeContact($tenant);
        $product = Product::create([
            'tenant_id'     => $tenant->id,
            'name'          => 'Steel Chair',
            'type'          => 'finished_good',
            'rate'          => 1000,
            'tax_percent'   => 18,
            'current_stock' => 20,
        ]);

        $this->actingAs($admin)->post(route('tenant.invoices.store'), $this->invoicePayload($contact, [
            'product_id' => $product->id,
            'quantity'   => 3,
        ]));

        $invoice = Invoice::first();
        $this->assertEqualsWithDelta(17.0, (float) $product->fresh()->current_stock, 0.01);

        // Bump the quantity from 3 to 5 — stock should drop by exactly 2 more
        // (not re-apply the full new quantity on top of the already-decremented stock).
        $this->actingAs($admin)->put(route('tenant.invoices.update', $invoice->id), $this->invoicePayload($contact, [
            'product_id' => $product->id,
            'quantity'   => 5,
        ]))->assertRedirect(route('tenant.invoices.show', $invoice->id));

        $this->assertEqualsWithDelta(15.0, (float) $product->fresh()->current_stock, 0.01);

        // Now drop it back down to 1 — stock should recover the difference.
        $this->actingAs($admin)->put(route('tenant.invoices.update', $invoice->id), $this->invoicePayload($contact, [
            'product_id' => $product->id,
            'quantity'   => 1,
        ]))->assertRedirect(route('tenant.invoices.show', $invoice->id));

        $this->assertEqualsWithDelta(19.0, (float) $product->fresh()->current_stock, 0.01);
    }

    public function test_invoice_item_without_a_product_id_does_not_touch_stock(): void
    {
        $tenant  = $this->setUpTenant();
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = $this->makeContact($tenant);
        $product = Product::create([
            'tenant_id'     => $tenant->id,
            'name'          => 'Steel Chair',
            'type'          => 'finished_good',
            'rate'          => 1000,
            'tax_percent'   => 18,
            'current_stock' => 20,
        ]);

        // Free-text item, no product_id at all.
        $this->actingAs($admin)->post(route('tenant.invoices.store'), $this->invoicePayload($contact));

        $this->assertEqualsWithDelta(20.0, (float) $product->fresh()->current_stock, 0.01);
    }

    public function test_stock_never_goes_negative_even_if_oversold(): void
    {
        $tenant  = $this->setUpTenant();
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = $this->makeContact($tenant);
        $product = Product::create([
            'tenant_id'     => $tenant->id,
            'name'          => 'Steel Chair',
            'type'          => 'finished_good',
            'rate'          => 1000,
            'tax_percent'   => 18,
            'current_stock' => 2,
        ]);

        $this->actingAs($admin)->post(route('tenant.invoices.store'), $this->invoicePayload($contact, [
            'product_id' => $product->id,
            'quantity'   => 5,
        ]));

        $this->assertEqualsWithDelta(0.0, (float) $product->fresh()->current_stock, 0.01);
    }

    public function test_low_stock_notification_fires_once_when_crossing_the_reorder_line(): void
    {
        $tenant  = $this->setUpTenant();
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $contact = $this->makeContact($tenant);
        $product = Product::create([
            'tenant_id'     => $tenant->id,
            'name'          => 'Steel Chair',
            'type'          => 'finished_good',
            'rate'          => 1000,
            'tax_percent'   => 18,
            'current_stock' => 10,
            'reorder_level' => 5,
        ]);

        $this->actingAs($admin)->post(route('tenant.invoices.store'), $this->invoicePayload($contact, [
            'product_id' => $product->id,
            'quantity'   => 6, // 10 -> 4, crosses the reorder_level of 5
        ]));

        $this->assertEqualsWithDelta(4.0, (float) $product->fresh()->current_stock, 0.01);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'type'    => 'product.low_stock',
        ]);
    }
}
