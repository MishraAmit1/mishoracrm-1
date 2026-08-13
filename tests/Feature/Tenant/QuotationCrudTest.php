<?php

namespace Tests\Feature\Tenant;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class QuotationCrudTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'date'  => now()->toDateString(),
            'items' => [[
                'name'        => 'Website Design',
                'quantity'    => 1,
                'rate'        => 10000,
                'amount'      => 10000,
                'tax_percent' => 18,
            ]],
        ], $overrides);
    }

    public function test_tenant_admin_can_create_a_quotation(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $response = $this->actingAs($admin)->post(route('tenant.quotations.store'), $this->validPayload());

        $quotation = Quotation::first();

        $this->assertNotNull($quotation);
        $response->assertRedirect(route('tenant.quotations.show', $quotation->id));
        $this->assertSame($tenant->id, $quotation->tenant_id);
        $this->assertSame(1, $quotation->version);
        $this->assertNull($quotation->parent_quotation_id);
        $this->assertEqualsWithDelta(11800.0, (float) $quotation->total, 0.01);
    }

    public function test_show_edit_update_happy_path(): void
    {
        $tenant    = $this->setUpTenant();
        $admin     = $this->makeUser($tenant, 'tenant_admin');
        $quotation = Quotation::factory()->create(['tenant_id' => $tenant->id, 'created_by' => $admin->id]);

        $this->actingAs($admin)->get(route('tenant.quotations.show', $quotation->id))->assertOk();
        $this->actingAs($admin)->get(route('tenant.quotations.edit', $quotation->id))->assertOk();

        $response = $this->actingAs($admin)->put(
            route('tenant.quotations.update', $quotation->id),
            $this->validPayload(['notes' => 'Updated notes'])
        );

        $response->assertRedirect(route('tenant.quotations.show', $quotation->id));
        $this->assertSame('Updated notes', $quotation->fresh()->notes);
    }

    public function test_destroy_deletes_a_quotation(): void
    {
        $tenant    = $this->setUpTenant();
        $admin     = $this->makeUser($tenant, 'tenant_admin');
        $quotation = Quotation::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($admin)->delete(route('tenant.quotations.destroy', $quotation->id))
            ->assertRedirect(route('tenant.quotations.index'));

        $this->assertSoftDeleted($quotation);
    }

    public function test_marking_status_accepted_auto_creates_invoice(): void
    {
        $tenant    = $this->setUpTenant();
        $admin     = $this->makeUser($tenant, 'tenant_admin');
        $quotation = Quotation::factory()->create(['tenant_id' => $tenant->id, 'status' => 'sent']);

        $this->actingAs($admin)->post(route('tenant.quotations.update_status', $quotation->id), [
            'status' => 'accepted',
        ])->assertRedirect();

        $quotation->refresh();
        $this->assertSame('accepted', $quotation->status);
        $this->assertDatabaseHas('invoices', ['quotation_id' => $quotation->id]);
        $this->assertSame(1, Invoice::where('quotation_id', $quotation->id)->count());
    }

    public function test_tenant_admin_can_export_quotations(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        Quotation::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($admin)->get(route('tenant.quotations.export'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_new_version_clones_quotation_as_draft_with_incremented_version(): void
    {
        $tenant    = $this->setUpTenant();
        $admin     = $this->makeUser($tenant, 'tenant_admin');
        $quotation = Quotation::factory()->create([
            'tenant_id' => $tenant->id,
            'status'    => 'sent',
            'total'     => 5000,
        ]);

        $response = $this->actingAs($admin)->post(route('tenant.quotations.new_version', $quotation->id));

        $newVersion = Quotation::where('parent_quotation_id', $quotation->id)->first();

        $this->assertNotNull($newVersion);
        $response->assertRedirect(route('tenant.quotations.edit', $newVersion->id));
        $this->assertSame(2, $newVersion->version);
        $this->assertSame('draft', $newVersion->status);
        $this->assertSame($quotation->id, $newVersion->parent_quotation_id);
        $this->assertNotSame($quotation->number, $newVersion->number);
    }

    public function test_a_quotation_from_another_tenant_returns_404(): void
    {
        $tenantA    = $this->setUpTenant();
        $tenantB    = Tenant::factory()->create();
        $adminA     = $this->makeUser($tenantA, 'tenant_admin');
        $quotationB = Quotation::factory()->create(['tenant_id' => $tenantB->id]);

        $this->actingAs($adminA)->get(route('tenant.quotations.show', $quotationB->id))
            ->assertNotFound();
    }

    public function test_quotation_defaults_to_inr_when_currency_not_specified(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $this->actingAs($admin)->post(route('tenant.quotations.store'), $this->validPayload());

        $quotation = Quotation::first();
        $this->assertSame('INR', $quotation->currency);
        $this->assertStringStartsWith('₹', $quotation->formatted_total);
    }

    public function test_quotation_can_be_created_with_a_different_currency(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $this->actingAs($admin)->post(route('tenant.quotations.store'), $this->validPayload(['currency' => 'USD']));

        $quotation = Quotation::first();
        $this->assertSame('USD', $quotation->currency);
        $this->assertStringStartsWith('$', $quotation->formatted_total);
    }

    public function test_quotation_item_stores_linked_product_id(): void
    {
        $tenant  = $this->setUpTenant();
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $product = Product::create(['tenant_id' => $tenant->id, 'name' => 'Consulting Hour', 'rate' => 2000]);

        $this->actingAs($admin)->post(route('tenant.quotations.store'), $this->validPayload([
            'items' => [[
                'product_id'  => $product->id,
                'name'        => 'Consulting Hour',
                'quantity'    => 2,
                'rate'        => 2000,
                'amount'      => 4000,
                'tax_percent' => 18,
            ]],
        ]));

        $quotation = Quotation::first();
        $this->assertEquals($product->id, $quotation->items[0]['product_id']);
    }
}
