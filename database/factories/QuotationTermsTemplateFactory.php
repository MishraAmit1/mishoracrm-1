<?php

namespace Database\Factories;

use App\Models\QuotationTermsTemplate;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuotationTermsTemplate>
 */
class QuotationTermsTemplateFactory extends Factory
{
    protected $model = QuotationTermsTemplate::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name'      => fake()->words(3, true) . ' Terms',
            'terms'     => fake()->paragraph(),
            'notes'     => fake()->sentence(),
        ];
    }
}
