<?php

namespace Tests\Feature\Tenant;

use App\Models\Tenant;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class VendorCrudTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name'    => 'Sharma Traders',
            'company' => 'Sharma Traders Pvt Ltd',
            'phone'   => '9876543210',
            'email'   => 'contact@sharmatraders.test',
        ], $overrides);
    }

    public function test_tenant_admin_can_create_a_vendor(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $response = $this->actingAs($admin)->post(route('tenant.vendors.store'), $this->validPayload());

        $vendor = Vendor::first();

        $this->assertNotNull($vendor);
        $response->assertRedirect(route('tenant.vendors.show', $vendor->id));
        $this->assertSame($tenant->id, $vendor->tenant_id);
        $this->assertSame('Sharma Traders', $vendor->name);
    }

    public function test_show_edit_update_happy_path(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $vendor = Vendor::create(array_merge($this->validPayload(), ['tenant_id' => $tenant->id]));

        $this->actingAs($admin)->get(route('tenant.vendors.show', $vendor->id))->assertOk();
        $this->actingAs($admin)->get(route('tenant.vendors.edit', $vendor->id))->assertOk();

        $response = $this->actingAs($admin)->put(
            route('tenant.vendors.update', $vendor->id),
            $this->validPayload(['notes' => 'Preferred vendor for electronics'])
        );

        $response->assertRedirect(route('tenant.vendors.show', $vendor->id));
        $this->assertSame('Preferred vendor for electronics', $vendor->fresh()->notes);
    }

    public function test_destroy_soft_deletes_a_vendor(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $vendor = Vendor::create(array_merge($this->validPayload(), ['tenant_id' => $tenant->id]));

        $this->actingAs($admin)->delete(route('tenant.vendors.destroy', $vendor->id))
            ->assertRedirect(route('tenant.vendors.index'));

        $this->assertSoftDeleted($vendor);
    }

    public function test_a_vendor_from_another_tenant_returns_404(): void
    {
        $tenantA = $this->setUpTenant();
        $tenantB = Tenant::factory()->create();
        $adminA  = $this->makeUser($tenantA, 'tenant_admin');
        $vendorB = Vendor::create(array_merge($this->validPayload(), ['tenant_id' => $tenantB->id]));

        $this->actingAs($adminA)->get(route('tenant.vendors.show', $vendorB->id))
            ->assertNotFound();
    }

    public function test_staff_without_vendors_create_permission_is_forbidden(): void
    {
        $tenant = $this->setUpTenant();
        $staff  = $this->makeUser($tenant, 'staff');

        $this->actingAs($staff)->post(route('tenant.vendors.store'), $this->validPayload())
            ->assertForbidden();
    }
}
