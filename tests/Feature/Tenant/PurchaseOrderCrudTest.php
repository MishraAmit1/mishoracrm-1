<?php

namespace Tests\Feature\Tenant;

use App\Models\PurchaseOrder;
use App\Models\Tenant;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class PurchaseOrderCrudTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'date'  => now()->toDateString(),
            'items' => [[
                'name'        => 'Office Chairs',
                'quantity'    => 10,
                'rate'        => 2000,
                'amount'      => 20000,
                'tax_percent' => 18,
            ]],
        ], $overrides);
    }

    public function test_tenant_admin_can_create_a_purchase_order(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $response = $this->actingAs($admin)->post(route('tenant.purchase-orders.store'), $this->validPayload());

        $po = PurchaseOrder::first();

        $this->assertNotNull($po);
        $response->assertRedirect(route('tenant.purchase-orders.show', $po->id));
        $this->assertSame($tenant->id, $po->tenant_id);
        $this->assertSame($admin->id, $po->created_by);
        $this->assertSame('draft', $po->status);
        $this->assertEqualsWithDelta(23600.0, (float) $po->total, 0.01);
    }

    public function test_show_edit_update_happy_path(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $po     = PurchaseOrder::factory()->create(['tenant_id' => $tenant->id, 'created_by' => $admin->id]);

        $this->actingAs($admin)->get(route('tenant.purchase-orders.show', $po->id))->assertOk();
        $this->actingAs($admin)->get(route('tenant.purchase-orders.edit', $po->id))->assertOk();

        $response = $this->actingAs($admin)->put(
            route('tenant.purchase-orders.update', $po->id),
            $this->validPayload(['notes' => 'Updated notes'])
        );

        $response->assertRedirect(route('tenant.purchase-orders.show', $po->id));
        $this->assertSame('Updated notes', $po->fresh()->notes);
    }

    public function test_destroy_soft_deletes_a_purchase_order(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $po     = PurchaseOrder::factory()->create(['tenant_id' => $tenant->id, 'created_by' => $admin->id]);

        $this->actingAs($admin)->delete(route('tenant.purchase-orders.destroy', $po->id))
            ->assertRedirect(route('tenant.purchase-orders.index'));

        $this->assertSoftDeleted($po);
    }

    public function test_marking_sent_requires_a_vendor(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $po     = PurchaseOrder::factory()->create(['tenant_id' => $tenant->id, 'created_by' => $admin->id, 'vendor_id' => null]);

        $response = $this->actingAs($admin)->post(route('tenant.purchase-orders.update_status', $po->id), [
            'status' => 'sent',
        ]);

        $response->assertRedirect();
        $this->assertSame('draft', $po->fresh()->status);

        $vendor = Vendor::create(['tenant_id' => $tenant->id, 'name' => 'Acme Supplies']);
        $po->update(['vendor_id' => $vendor->id]);

        $this->actingAs($admin)->post(route('tenant.purchase-orders.update_status', $po->id), [
            'status' => 'sent',
        ])->assertRedirect();

        $this->assertSame('sent', $po->fresh()->status);
    }

    public function test_tenant_admin_can_export_purchase_orders(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        PurchaseOrder::factory()->create(['tenant_id' => $tenant->id, 'created_by' => $admin->id]);

        $this->actingAs($admin)->get(route('tenant.purchase-orders.export'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_a_purchase_order_from_another_tenant_returns_404(): void
    {
        $tenantA = $this->setUpTenant();
        $tenantB = Tenant::factory()->create();
        $adminA  = $this->makeUser($tenantA, 'tenant_admin');
        $poB     = PurchaseOrder::factory()->create(['tenant_id' => $tenantB->id]);

        $this->actingAs($adminA)->get(route('tenant.purchase-orders.show', $poB->id))
            ->assertNotFound();
    }

    public function test_receive_flow_partial_then_full(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $vendor = Vendor::create(['tenant_id' => $tenant->id, 'name' => 'Acme Supplies']);
        $po     = PurchaseOrder::factory()->create([
            'tenant_id'  => $tenant->id,
            'created_by' => $admin->id,
            'vendor_id'  => $vendor->id,
            'status'     => 'sent',
            'items'      => [[
                'name'               => 'Office Chairs',
                'quantity'           => 10,
                'rate'               => 2000,
                'tax_percent'        => 18,
                'amount'             => 20000,
                'received_quantity'  => 0,
            ]],
        ]);

        // Partial receive
        $this->actingAs($admin)->post(route('tenant.purchase-orders.receive', $po->id), [
            'items' => [
                ['received_quantity' => 4],
            ],
        ])->assertRedirect(route('tenant.purchase-orders.show', $po->id));

        $po->refresh();
        $this->assertSame('partially_received', $po->status);
        $this->assertSame(4.0, (float) $po->items[0]['received_quantity']);

        // Full receive
        $this->actingAs($admin)->post(route('tenant.purchase-orders.receive', $po->id), [
            'items' => [
                ['received_quantity' => 10],
            ],
        ])->assertRedirect(route('tenant.purchase-orders.show', $po->id));

        $po->refresh();
        $this->assertSame('received', $po->status);
        $this->assertSame(10.0, (float) $po->items[0]['received_quantity']);
    }

    public function test_receive_is_blocked_when_purchase_order_is_still_draft(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $po     = PurchaseOrder::factory()->create(['tenant_id' => $tenant->id, 'created_by' => $admin->id, 'status' => 'draft']);

        $this->actingAs($admin)->post(route('tenant.purchase-orders.receive', $po->id), [
            'items' => [['received_quantity' => 1]],
        ])->assertForbidden();
    }
}
