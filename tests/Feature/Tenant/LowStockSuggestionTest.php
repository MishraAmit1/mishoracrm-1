<?php

namespace Tests\Feature\Tenant;

use App\Models\BillOfMaterialItem;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class LowStockSuggestionTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function buildFinishedGoodWithBom(Tenant $tenant): Product
    {
        $finishedGood = Product::create([
            'tenant_id'        => $tenant->id,
            'name'             => 'Steel Chair',
            'type'             => 'finished_good',
            'rate'             => 1500,
            'tax_percent'      => 18,
            'current_stock'    => 3,
            'reorder_level'    => 5,
            'reorder_quantity' => 20,
        ]);

        $rod = Product::create([
            'tenant_id'     => $tenant->id,
            'name'          => 'Steel Rod',
            'type'          => 'raw_material',
            'rate'          => 50,
            'tax_percent'   => 18,
            'current_stock' => 30,
        ]);

        BillOfMaterialItem::create([
            'tenant_id'         => $tenant->id,
            'product_id'        => $finishedGood->id,
            'material_id'       => $rod->id,
            'quantity_per_unit' => 5,
        ]);

        return $finishedGood;
    }

    public function test_low_stock_page_lists_products_at_or_below_reorder_level(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $finishedGood = $this->buildFinishedGoodWithBom($tenant);

        // A well-stocked product should not appear.
        Product::create([
            'tenant_id'     => $tenant->id,
            'name'          => 'Plastic Chair',
            'type'          => 'finished_good',
            'rate'          => 800,
            'tax_percent'   => 18,
            'current_stock' => 100,
            'reorder_level' => 5,
        ]);

        $response = $this->actingAs($admin)->get(route('tenant.products.low-stock'));

        $response->assertOk();
        $response->assertSee('Steel Chair');
        $response->assertDontSee('Plastic Chair');
    }

    public function test_suggest_from_prefills_the_expected_item_rows(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $finishedGood = $this->buildFinishedGoodWithBom($tenant);

        // needed = 5 * 20 = 100, in stock = 30, shortfall = 70
        $response = $this->actingAs($admin)->get(
            route('tenant.purchase-requests.create', ['suggest_from' => $finishedGood->id])
        );

        $response->assertOk();
        $response->assertSee('Steel Rod');
        $response->assertSee('70'); // shortfall quantity baked into the prefill JSON
    }

    public function test_a_product_from_another_tenant_cannot_be_used_as_a_suggestion_source(): void
    {
        $tenantA = $this->setUpTenant();
        $adminA  = $this->makeUser($tenantA, 'tenant_admin');

        $tenantB = Tenant::factory()->create();
        $finishedGoodB = $this->buildFinishedGoodWithBom($tenantB);

        $response = $this->actingAs($adminA)->get(
            route('tenant.purchase-requests.create', ['suggest_from' => $finishedGoodB->id])
        );

        // Cross-tenant product is silently ignored — the create page still
        // renders normally, just without any prefill.
        $response->assertOk();
        $response->assertDontSee('Steel Rod');
    }
}
