<?php

namespace Tests\Feature\Tenant;

use App\Models\BillOfMaterialItem;
use App\Models\Product;
use App\Models\WorkOrder;
use App\Services\StockService;
use App\Services\WorkOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class WorkOrderStagesTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function setup_chair($tenant): array
    {
        $chair = Product::create([
            'tenant_id' => $tenant->id, 'name' => 'Steel Chair', 'type' => 'finished_good',
            'rate' => 1500, 'cost_price' => 1500, 'tax_percent' => 18, 'current_stock' => 0,
        ]);
        $rod = Product::create([
            'tenant_id' => $tenant->id, 'name' => 'Steel Rod', 'type' => 'raw_material',
            'rate' => 100, 'cost_price' => 60, 'tax_percent' => 18, 'current_stock' => 0,
        ]);
        StockService::receiveBatch($rod, 100, 'ROD-1', null, null, null, 60);
        BillOfMaterialItem::create([
            'tenant_id' => $tenant->id, 'product_id' => $chair->id,
            'material_id' => $rod->id, 'quantity_per_unit' => 5,
        ]);

        return [$chair, $rod];
    }

    private function woWithStages($tenant, $admin, Product $chair): WorkOrder
    {
        $this->actingAs($admin);

        return WorkOrderService::store([
            'product_id' => $chair->id,
            'quantity'   => 10,
            'stages'     => [
                ['name' => 'Cutting'],
                ['name' => 'Welding', 'assigned_to' => $admin->id],
                ['name' => 'QC'],
                ['name' => ''], // blank rows are dropped
            ],
        ], $tenant->id, $admin->id);
    }

    public function test_stages_are_created_in_sequence_and_blanks_dropped(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        [$chair] = $this->setup_chair($tenant);

        $wo = $this->woWithStages($tenant, $admin, $chair);

        $this->assertSame(3, $wo->stages()->count());
        $this->assertSame(['Cutting', 'Welding', 'QC'], $wo->stages()->pluck('name')->all());
        $this->assertSame([1, 2, 3], $wo->stages()->pluck('sequence')->all());
    }

    public function test_stages_are_not_actionable_until_production_starts(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        [$chair] = $this->setup_chair($tenant);
        $wo = $this->woWithStages($tenant, $admin, $chair);

        $this->expectException(ValidationException::class);
        WorkOrderService::startStage($wo->stages()->first());
    }

    public function test_work_order_cannot_complete_with_open_stages(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        [$chair] = $this->setup_chair($tenant);
        $wo = $this->woWithStages($tenant, $admin, $chair);

        WorkOrderService::start($wo->fresh());

        try {
            WorkOrderService::complete($wo->fresh());
            $this->fail('Expected completion to be blocked by open stages.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('stage', strtolower(collect($e->errors())->flatten()->first()));
        }

        $this->assertSame('in_progress', $wo->fresh()->status);
    }

    public function test_finishing_or_skipping_every_stage_unblocks_completion(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        [$chair, $rod] = $this->setup_chair($tenant);
        $wo = $this->woWithStages($tenant, $admin, $chair);

        WorkOrderService::start($wo->fresh());

        $stages = $wo->fresh()->stages;
        WorkOrderService::startStage($stages[0]);
        WorkOrderService::completeStage($stages[0]->fresh());
        WorkOrderService::completeStage($stages[1]);
        WorkOrderService::skipStage($stages[2]);

        WorkOrderService::complete($wo->fresh(), null);

        $this->assertSame('completed', $wo->fresh()->status);
        $this->assertEqualsWithDelta(10.0, (float) $chair->fresh()->current_stock, 0.01);
    }

    public function test_stage_action_endpoint_transitions_state(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $settings = $tenant->settings ?? [];
        $settings['modules']['manufacturing'] = true;
        $tenant->update(['settings' => $settings]);
        [$chair] = $this->setup_chair($tenant);
        $wo = $this->woWithStages($tenant, $admin, $chair);
        WorkOrderService::start($wo->fresh());

        $stage = $wo->fresh()->stages()->first();

        $this->actingAs($admin)->post(route('tenant.work-orders.stages.action', [$wo->id, $stage->id]), ['action' => 'complete'])
            ->assertRedirect();

        $this->assertSame('done', $stage->fresh()->status);
    }
}
