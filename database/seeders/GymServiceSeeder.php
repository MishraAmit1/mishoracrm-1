<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class GymServiceSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();
        if (!$tenant) {
            $this->command->error('No tenant found. Create a tenant first.');
            return;
        }

        $services = [
            ['service_code' => 'GYM-MON',  'name' => 'Monthly Membership',              'description' => 'Full gym access, billed every month',        'rate' => 1500,  'tax_percent' => 18, 'hsn' => '999723', 'unit' => 'month',   'billing_cycle' => 'monthly',   'duration_value' => null, 'duration_unit' => null],
            ['service_code' => 'GYM-QTR',  'name' => 'Quarterly Membership',            'description' => 'Full gym access, billed every quarter',       'rate' => 4000,  'tax_percent' => 18, 'hsn' => '999723', 'unit' => 'quarter', 'billing_cycle' => 'quarterly', 'duration_value' => null, 'duration_unit' => null],
            ['service_code' => 'GYM-YR',   'name' => 'Annual Membership',               'description' => 'Full gym access, billed once a year',         'rate' => 15000, 'tax_percent' => 18, 'hsn' => '999723', 'unit' => 'year',    'billing_cycle' => 'yearly',    'duration_value' => 12,   'duration_unit' => 'months'],
            ['service_code' => 'GYM-PT10', 'name' => 'Personal Training (10 sessions)', 'description' => 'One-on-one personal training package',       'rate' => 5000,  'tax_percent' => 18, 'hsn' => '999723', 'unit' => 'package', 'billing_cycle' => 'one_time',  'duration_value' => null, 'duration_unit' => null],
            ['service_code' => 'GYM-DIET', 'name' => 'Diet Consultation',               'description' => 'One-time diet plan consultation with trainer','rate' => 1000,  'tax_percent' => 18, 'hsn' => '999723', 'unit' => 'session', 'billing_cycle' => 'one_time',  'duration_value' => null, 'duration_unit' => null],
        ];

        foreach ($services as $s) {
            Service::create(array_merge($s, [
                'tenant_id' => $tenant->id,
                'is_active' => true,
            ]));
        }

        $this->command->info("✓ " . count($services) . " gym services added for tenant: {$tenant->name}");
    }
}
