<?php

namespace Tests\Feature\Tenant;

use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class PurchaseRequestCrudTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'date'  => now()->toDateString(),
            'items' => [[
                'name'     => 'Office Chairs',
                'quantity' => 5,
                'reason'   => 'New hires onboarding',
            ]],
            'reason' => 'Expanding the sales team desk setup',
        ], $overrides);
    }

    public function test_staff_can_create_a_purchase_request(): void
    {
        $tenant = $this->setUpTenant();
        $staff  = $this->makeUser($tenant, 'staff');

        $response = $this->actingAs($staff)->post(route('tenant.purchase-requests.store'), $this->validPayload());

        $pr = PurchaseRequest::first();

        $this->assertNotNull($pr);
        $response->assertRedirect(route('tenant.purchase-requests.show', $pr->id));
        $this->assertSame($tenant->id, $pr->tenant_id);
        $this->assertSame('pending', $pr->status);
        $this->assertSame($staff->id, $pr->requested_by);
    }

    public function test_show_edit_update_happy_path_while_pending(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $pr     = PurchaseRequest::factory()->create([
            'tenant_id'    => $tenant->id,
            'requested_by' => $admin->id,
            'status'       => 'pending',
        ]);

        $this->actingAs($admin)->get(route('tenant.purchase-requests.show', $pr->id))->assertOk();
        $this->actingAs($admin)->get(route('tenant.purchase-requests.edit', $pr->id))->assertOk();

        $response = $this->actingAs($admin)->put(
            route('tenant.purchase-requests.update', $pr->id),
            $this->validPayload(['reason' => 'Updated reason'])
        );

        $response->assertRedirect(route('tenant.purchase-requests.show', $pr->id));
        $this->assertSame('Updated reason', $pr->fresh()->reason);
    }

    public function test_edit_is_blocked_once_approved(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $pr     = PurchaseRequest::factory()->create([
            'tenant_id'    => $tenant->id,
            'requested_by' => $admin->id,
            'status'       => 'approved',
        ]);

        $this->actingAs($admin)->get(route('tenant.purchase-requests.edit', $pr->id))->assertForbidden();
        $this->actingAs($admin)->put(route('tenant.purchase-requests.update', $pr->id), $this->validPayload())
            ->assertForbidden();
    }

    public function test_destroy_soft_deletes_a_purchase_request(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $pr     = PurchaseRequest::factory()->create(['tenant_id' => $tenant->id, 'requested_by' => $admin->id]);

        $this->actingAs($admin)->delete(route('tenant.purchase-requests.destroy', $pr->id))
            ->assertRedirect(route('tenant.purchase-requests.index'));

        $this->assertSoftDeleted($pr);
    }

    public function test_approve_auto_creates_linked_draft_purchase_order(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $pr     = PurchaseRequest::factory()->create([
            'tenant_id'    => $tenant->id,
            'requested_by' => $admin->id,
            'status'       => 'pending',
            'items'        => [[
                'name'     => 'Laptop Stand',
                'quantity' => 3,
            ]],
        ]);

        $response = $this->actingAs($admin)->post(route('tenant.purchase-requests.approve', $pr->id));
        $response->assertRedirect(route('tenant.purchase-requests.show', $pr->id));

        $pr->refresh();
        $this->assertSame('converted', $pr->status);
        $this->assertSame($admin->id, $pr->approved_by);
        $this->assertNotNull($pr->approved_at);

        $po = PurchaseOrder::where('purchase_request_id', $pr->id)->first();
        $this->assertNotNull($po);
        $this->assertSame('draft', $po->status);
        $this->assertNull($po->vendor_id);
        $this->assertSame(3.0, (float) $po->items[0]['quantity']);
        $this->assertSame(0.0, (float) $po->items[0]['rate']);
        $this->assertSame(0.0, (float) $po->total);
    }

    public function test_reject_stores_reason_and_flips_status(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $pr     = PurchaseRequest::factory()->create([
            'tenant_id'    => $tenant->id,
            'requested_by' => $admin->id,
            'status'       => 'pending',
        ]);

        $response = $this->actingAs($admin)->post(route('tenant.purchase-requests.reject', $pr->id), [
            'rejection_reason' => 'Budget not approved this quarter',
        ]);

        $response->assertRedirect(route('tenant.purchase-requests.show', $pr->id));

        $pr->refresh();
        $this->assertSame('rejected', $pr->status);
        $this->assertSame('Budget not approved this quarter', $pr->rejection_reason);
    }

    public function test_a_purchase_request_from_another_tenant_returns_404(): void
    {
        $tenantA = $this->setUpTenant();
        $tenantB = Tenant::factory()->create();
        $adminA  = $this->makeUser($tenantA, 'tenant_admin');
        $prB     = PurchaseRequest::factory()->create(['tenant_id' => $tenantB->id]);

        $this->actingAs($adminA)->get(route('tenant.purchase-requests.show', $prB->id))
            ->assertNotFound();
    }

    public function test_staff_without_approve_permission_cannot_approve(): void
    {
        $tenant = $this->setUpTenant();
        $staff  = $this->makeUser($tenant, 'staff');
        $pr     = PurchaseRequest::factory()->create(['tenant_id' => $tenant->id, 'requested_by' => $staff->id]);

        $this->actingAs($staff)->post(route('tenant.purchase-requests.approve', $pr->id))
            ->assertForbidden();
    }
}
