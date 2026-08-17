<?php

namespace Database\Factories;

use App\Models\PurchaseRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseRequest>
 */
class PurchaseRequestFactory extends Factory
{
    protected $model = PurchaseRequest::class;

    public function definition(): array
    {
        return [
            'tenant_id'    => Tenant::factory(),
            'number'       => 'PR-' . now()->format('Ymd') . '-' . Str::padLeft((string) fake()->unique()->numberBetween(1, 99999), 4, '0'),
            'requested_by' => User::factory(),
            'date'         => now()->toDateString(),
            'items'        => [[
                'name'     => fake()->words(3, true),
                'quantity' => fake()->numberBetween(1, 10),
            ]],
            'reason' => fake()->sentence(),
            'status' => 'pending',
        ];
    }

    public function status(string $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
