<?php

namespace Database\Factories;

use App\Models\Deal;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Deal>
 */
class DealFactory extends Factory
{
    protected $model = Deal::class;

    public function definition(): array
    {
        return [
            'tenant_id'   => Tenant::factory(),
            'title'       => fake()->sentence(3),
            'value'       => fake()->randomFloat(2, 1000, 500000),
            'stage'       => 'new',
            'probability' => 10,
        ];
    }

    public function stage(string $stage): static
    {
        return $this->state(fn () => ['stage' => $stage]);
    }

    public function assignedTo(int $userId): static
    {
        return $this->state(fn () => ['assigned_to' => $userId]);
    }
}
