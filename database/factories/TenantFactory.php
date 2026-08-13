<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'      => fake()->company(),
            'subdomain' => fake()->unique()->slug(2),
            'email'     => fake()->unique()->companyEmail(),
            'status'    => 'active',
        ];
    }
}
