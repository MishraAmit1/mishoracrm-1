<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        $items = [[
            'name'               => fake()->words(3, true),
            'description'        => fake()->sentence(),
            'quantity'           => 5,
            'rate'               => fake()->randomFloat(2, 100, 5000),
            'tax_percent'        => 18,
            'amount'             => 0,
            'received_quantity'  => 0,
        ]];
        $items[0]['amount'] = $items[0]['quantity'] * $items[0]['rate'];

        $totals = PurchaseOrder::calculateTotals($items, 0, 18);

        return array_merge([
            'tenant_id'  => Tenant::factory(),
            'number'     => 'PO-' . now()->format('Ymd') . '-' . Str::padLeft((string) fake()->unique()->numberBetween(1, 99999), 4, '0'),
            'date'       => now()->toDateString(),
            'items'      => $items,
            'status'     => 'draft',
            'created_by' => User::factory(),
        ], $totals);
    }

    public function status(string $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
