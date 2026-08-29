<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorBill;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VendorBill>
 */
class VendorBillFactory extends Factory
{
    protected $model = VendorBill::class;

    public function definition(): array
    {
        $items = [[
            'product_id'  => null,
            'name'        => fake()->words(3, true),
            'description' => null,
            'quantity'    => 4,
            'rate'        => 1000,
            'tax_percent' => 18,
            'amount'      => 4000,
        ]];

        $totals = VendorBill::calculateTotals($items, 0, 18);

        return array_merge([
            'tenant_id'   => Tenant::factory(),
            'vendor_id'   => Vendor::factory(),
            'number'      => 'BILL-' . now()->format('Ymd') . '-' . Str::padLeft((string) fake()->unique()->numberBetween(1, 99999), 4, '0'),
            'date'        => now()->toDateString(),
            'due_date'    => now()->addDays(30)->toDateString(),
            'items'       => $items,
            'amount_paid' => 0,
            'status'      => 'unpaid',
            'created_by'  => User::factory(),
        ], $totals);
    }

    public function status(string $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
