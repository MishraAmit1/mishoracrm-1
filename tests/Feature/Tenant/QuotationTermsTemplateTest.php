<?php

namespace Tests\Feature\Tenant;

use App\Models\QuotationTermsTemplate;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class QuotationTermsTemplateTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    public function test_tenant_admin_can_create_a_template(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $this->actingAs($admin)->post(route('tenant.quotation-terms-templates.store'), [
            'name'  => 'Standard Terms',
            'terms' => '50% advance, 50% on delivery.',
            'notes' => 'Thanks for your business.',
        ])->assertRedirect(route('tenant.quotation-terms-templates.index'));

        $this->assertDatabaseHas('quotation_terms_templates', [
            'tenant_id' => $tenant->id,
            'name'      => 'Standard Terms',
        ]);
    }

    public function test_staff_without_quotations_edit_permission_cannot_create_template(): void
    {
        $tenant = $this->setUpTenant();
        $staff  = $this->makeUser($tenant, 'staff');

        $this->actingAs($staff)->post(route('tenant.quotation-terms-templates.store'), [
            'name' => 'Blocked Template',
        ])->assertForbidden();

        $this->assertDatabaseMissing('quotation_terms_templates', ['name' => 'Blocked Template']);
    }

    public function test_can_update_and_delete_a_template(): void
    {
        $tenant   = $this->setUpTenant();
        $admin    = $this->makeUser($tenant, 'tenant_admin');
        $template = QuotationTermsTemplate::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($admin)->put(route('tenant.quotation-terms-templates.update', $template->id), [
            'name'  => 'Updated Name',
            'terms' => 'Updated terms',
        ])->assertRedirect(route('tenant.quotation-terms-templates.index'));

        $this->assertSame('Updated Name', $template->fresh()->name);

        $this->actingAs($admin)->delete(route('tenant.quotation-terms-templates.destroy', $template->id))
            ->assertRedirect(route('tenant.quotation-terms-templates.index'));

        $this->assertDatabaseMissing('quotation_terms_templates', ['id' => $template->id]);
    }

    public function test_a_template_from_another_tenant_returns_404(): void
    {
        $tenantA  = $this->setUpTenant();
        $tenantB  = Tenant::factory()->create();
        $adminA   = $this->makeUser($tenantA, 'tenant_admin');
        $template = QuotationTermsTemplate::factory()->create(['tenant_id' => $tenantB->id]);

        $this->actingAs($adminA)->get(route('tenant.quotation-terms-templates.edit', $template->id))
            ->assertNotFound();
    }
}
