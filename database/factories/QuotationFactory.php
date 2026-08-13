<?php

namespace Database\Factories;

use App\Models\Quotation;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Quotation>
 */
class QuotationFactory extends Factory
{
    protected $model = Quotation::class;

    public function definition(): array
    {
        $items = [[
            'name'        => fake()->words(3, true),
            'description' => fake()->sentence(),
            'quantity'    => 1,
            'rate'        => fake()->randomFloat(2, 1000, 50000),
            'tax_percent' => 18,
            'amount'      => 0,
        ]];
        $items[0]['amount'] = $items[0]['quantity'] * $items[0]['rate'];

        $totals = Quotation::calculateTotals($items, 0, 18);

        return array_merge([
            'tenant_id' => Tenant::factory(),
            'number'    => 'QT-' . now()->format('Ymd') . '-' . Str::padLeft((string) fake()->unique()->numberBetween(1, 99999), 4, '0'),
            'date'      => now()->toDateString(),
            'items'     => $items,
            'status'    => 'draft',
            'version'   => 1,
        ], $totals);
    }

    public function status(string $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
