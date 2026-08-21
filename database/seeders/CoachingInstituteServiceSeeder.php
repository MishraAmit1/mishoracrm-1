<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class CoachingInstituteServiceSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();
        if (!$tenant) {
            $this->command->error('No tenant found. Create a tenant first.');
            return;
        }

        // Fees are billed Monthly but each course itself runs a fixed number
        // of months — Billing Cycle and Contract Length differ on purpose.
        $services = [
            ['service_code' => 'EDU-ENG01', 'name' => 'Spoken English Course',        'description' => 'Monthly fees, 6-month course',                    'rate' => 2000,  'tax_percent' => 0, 'hsn' => '999293', 'unit' => 'month',  'billing_cycle' => 'monthly',  'duration_value' => 6,  'duration_unit' => 'months'],
            ['service_code' => 'EDU-CRS01', 'name' => 'JEE/NEET Crash Course',         'description' => 'One-time payment, 3-month intensive course',      'rate' => 25000, 'tax_percent' => 0, 'hsn' => '999293', 'unit' => 'course', 'billing_cycle' => 'one_time', 'duration_value' => 3,  'duration_unit' => 'months'],
            ['service_code' => 'EDU-PD001', 'name' => 'Personality Development Workshop', 'description' => 'One-time weekend workshop',                    'rate' => 3000,  'tax_percent' => 0, 'hsn' => '999293', 'unit' => 'workshop', 'billing_cycle' => 'one_time', 'duration_value' => null, 'duration_unit' => null],
            ['service_code' => 'EDU-COMP1', 'name' => 'Computer Basics Course',        'description' => 'Monthly fees, 3-month course',                    'rate' => 1500,  'tax_percent' => 0, 'hsn' => '999293', 'unit' => 'month',  'billing_cycle' => 'monthly',  'duration_value' => 3,  'duration_unit' => 'months'],
            ['service_code' => 'EDU-KIT01', 'name' => 'Study Material Kit',            'description' => 'One-time books and material kit',                 'rate' => 1200,  'tax_percent' => 12, 'hsn' => '490199', 'unit' => 'kit',    'billing_cycle' => 'one_time', 'duration_value' => null, 'duration_unit' => null],
        ];

        foreach ($services as $s) {
            Service::create(array_merge($s, [
                'tenant_id' => $tenant->id,
                'is_active' => true,
            ]));
        }

        $this->command->info("✓ " . count($services) . " Coaching Institute services added for tenant: {$tenant->name}");
    }
}
