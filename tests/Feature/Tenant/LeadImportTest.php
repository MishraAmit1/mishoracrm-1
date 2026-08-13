<?php

namespace Tests\Feature\Tenant;

use App\Models\Lead;
use App\Services\Import\LeadImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class LeadImportTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    public function test_process_creates_leads_from_mapped_rows(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $this->actingAs($admin);

        $mapping = [0 => 'name', 1 => 'phone', 2 => 'email', 3 => 'source'];
        $rows = [
            ['Alice', '9111111111', 'alice@example.com', 'website'],
            ['Bob',   '9222222222', 'bob@example.com',   'referral'],
        ];

        $result = (new LeadImportService())->process($tenant->id, $rows, $mapping);

        $this->assertSame(2, $result['created']);
        $this->assertSame(0, $result['skipped']);
        $this->assertCount(0, $result['errors']);
        $this->assertDatabaseHas('leads', ['phone' => '9111111111', 'tenant_id' => $tenant->id]);
        $this->assertDatabaseHas('leads', ['phone' => '9222222222', 'tenant_id' => $tenant->id]);
    }

    public function test_process_skips_duplicate_rows(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $this->actingAs($admin);

        Lead::factory()->create(['tenant_id' => $tenant->id, 'phone' => '9111111111']);

        $mapping = [0 => 'name', 1 => 'phone'];
        $rows    = [['Alice Dup', '9111111111']];

        $result = (new LeadImportService())->process($tenant->id, $rows, $mapping);

        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['skipped']);
    }

    public function test_process_reports_errors_for_missing_required_fields(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $this->actingAs($admin);

        $mapping = [0 => 'name', 1 => 'phone'];
        $rows    = [
            ['', '9111111111'],   // missing name
            ['No Phone', ''],     // missing phone
        ];

        $result = (new LeadImportService())->process($tenant->id, $rows, $mapping);

        $this->assertSame(0, $result['created']);
        $this->assertCount(2, $result['errors']);
    }

    public function test_process_drops_invalid_enum_values_instead_of_failing(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $this->actingAs($admin);

        $mapping = [0 => 'name', 1 => 'phone', 2 => 'source', 3 => 'status'];
        $rows    = [['Charlie', '9333333333', 'not_a_real_source', 'not_a_real_status']];

        $result = (new LeadImportService())->process($tenant->id, $rows, $mapping);

        $this->assertSame(1, $result['created']);
        $lead = Lead::where('phone', '9333333333')->first();
        $this->assertSame('other', $lead->source);
        $this->assertSame('new', $lead->status);
    }

    public function test_import_route_is_gated_by_leads_import_permission(): void
    {
        $tenant = $this->setUpTenant();
        $role   = Role::firstOrCreate(['name' => 'no_import', 'guard_name' => 'web']);
        $role->syncPermissions([]);
        $user = $this->makeUser($tenant, 'no_import');

        $this->actingAs($user)->get(route('tenant.leads.import'))->assertForbidden();

        $admin = $this->makeUser($tenant, 'tenant_admin');
        $this->actingAs($admin)->get(route('tenant.leads.import'))->assertOk();
    }
}
