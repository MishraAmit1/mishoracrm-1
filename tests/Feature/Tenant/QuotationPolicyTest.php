<?php

namespace Tests\Feature\Tenant;

use App\Models\Quotation;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class QuotationPolicyTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function roleWith(Tenant $tenant, string $roleName, array $permissions): void
    {
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        $role->syncPermissions($permissions);
    }

    private function validPayload(): array
    {
        return [
            'date'  => now()->toDateString(),
            'items' => [[
                'name'        => 'Website Design',
                'quantity'    => 1,
                'rate'        => 10000,
                'amount'      => 10000,
                'tax_percent' => 18,
            ]],
        ];
    }

    public function test_view_own_user_can_see_only_own_created_quotations(): void
    {
        $tenant = $this->setUpTenant();
        $this->roleWith($tenant, 'quotations_view_own_only', ['quotations.view_own']);
        $user      = $this->makeUser($tenant, 'quotations_view_own_only');
        $otherUser = $this->makeUser($tenant, 'quotations_view_own_only');

        $myQuotation    = Quotation::factory()->create(['tenant_id' => $tenant->id, 'created_by' => $user->id]);
        $otherQuotation = Quotation::factory()->create(['tenant_id' => $tenant->id, 'created_by' => $otherUser->id]);

        $this->actingAs($user)->get(route('tenant.quotations.show', $myQuotation->id))->assertOk();
        $this->actingAs($user)->get(route('tenant.quotations.show', $otherQuotation->id))->assertForbidden();
    }

    public function test_view_all_user_can_see_any_tenant_quotation(): void
    {
        $tenant = $this->setUpTenant();
        $this->roleWith($tenant, 'quotations_view_all_only', ['quotations.view_all']);
        $user = $this->makeUser($tenant, 'quotations_view_all_only');

        $quotation = Quotation::factory()->create(['tenant_id' => $tenant->id, 'created_by' => null]);

        $this->actingAs($user)->get(route('tenant.quotations.show', $quotation->id))->assertOk();
    }

    // Regression test: store() previously never called authorize('create', Quotation::class)
    // and the policy's create() unconditionally returned true, so any authenticated user —
    // even one with zero quotations permissions — could create quotations.
    public function test_store_requires_quotations_create_permission(): void
    {
        $tenant = $this->setUpTenant();
        $this->roleWith($tenant, 'quotations_no_create', ['quotations.view_all']);
        $user = $this->makeUser($tenant, 'quotations_no_create');

        $this->actingAs($user)->post(route('tenant.quotations.store'), $this->validPayload())
            ->assertForbidden();

        $this->assertDatabaseCount('quotations', 0);
    }

    public function test_store_allowed_with_quotations_create_permission(): void
    {
        $tenant = $this->setUpTenant();
        $this->roleWith($tenant, 'quotations_can_create', ['quotations.create']);
        $user = $this->makeUser($tenant, 'quotations_can_create');

        $this->actingAs($user)->post(route('tenant.quotations.store'), $this->validPayload())
            ->assertRedirect();

        $this->assertDatabaseCount('quotations', 1);
    }

    public function test_edit_requires_quotations_edit_permission(): void
    {
        $tenant = $this->setUpTenant();
        $this->roleWith($tenant, 'quotations_view_all_no_edit', ['quotations.view_all']);
        $user      = $this->makeUser($tenant, 'quotations_view_all_no_edit');
        $quotation = Quotation::factory()->create(['tenant_id' => $tenant->id]);

        $payload = $this->validPayload();

        $this->actingAs($user)->put(route('tenant.quotations.update', $quotation->id), $payload)
            ->assertForbidden();
    }

    public function test_destroy_requires_quotations_delete_permission(): void
    {
        $tenant = $this->setUpTenant();
        $this->roleWith($tenant, 'quotations_view_all_no_delete', ['quotations.view_all']);
        $user      = $this->makeUser($tenant, 'quotations_view_all_no_delete');
        $quotation = Quotation::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)->delete(route('tenant.quotations.destroy', $quotation->id))
            ->assertForbidden();

        $this->assertNotSoftDeleted($quotation);
    }

    public function test_a_quotation_from_another_tenant_returns_404_not_403(): void
    {
        $tenantA   = $this->setUpTenant();
        $tenantB   = Tenant::factory()->create();
        $admin     = $this->makeUser($tenantA, 'tenant_admin');
        $quotationB = Quotation::factory()->create(['tenant_id' => $tenantB->id]);

        $this->actingAs($admin)->get(route('tenant.quotations.show', $quotationB->id))
            ->assertNotFound();
    }

    // Regression test: send() only checked the view ability, so the seeded
    // quotations.send permission was never actually enforced.
    public function test_send_requires_quotations_send_permission(): void
    {
        $tenant = $this->setUpTenant();
        $this->roleWith($tenant, 'quotations_view_all_no_send', ['quotations.view_all']);
        $user      = $this->makeUser($tenant, 'quotations_view_all_no_send');
        $quotation = Quotation::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)->post(route('tenant.quotations.send', $quotation->id))
            ->assertForbidden();
    }

    public function test_export_requires_quotations_export_permission(): void
    {
        $tenant = $this->setUpTenant();
        $staff  = $this->makeUser($tenant, 'staff');

        $this->actingAs($staff)->get(route('tenant.quotations.export'))
            ->assertForbidden();
    }
}
