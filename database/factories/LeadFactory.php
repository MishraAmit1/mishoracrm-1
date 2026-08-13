<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'tenant_id'  => Tenant::factory(),
            'name'       => fake()->name(),
            'phone'      => fake()->unique()->numerify('9#########'),
            'email'      => fake()->unique()->safeEmail(),
            'source'     => fake()->randomElement(array_keys(Lead::sources())),
            'status'     => 'new',
            'priority'   => fake()->randomElement(['low', 'medium', 'high']),
            'lead_value' => fake()->randomFloat(2, 1000, 100000),
        ];
    }

    public function status(string $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }

    public function assignedTo(int $userId): static
    {
        return $this->state(fn () => ['assigned_to' => $userId]);
    }

    public function unassigned(): static
    {
        return $this->state(fn () => ['assigned_to' => null]);
    }
}
