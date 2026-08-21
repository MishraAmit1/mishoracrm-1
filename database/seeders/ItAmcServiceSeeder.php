<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class ItAmcServiceSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();
        if (!$tenant) {
            $this->command->error('No tenant found. Create a tenant first.');
            return;
        }

        // Billed Quarterly but the underlying AMC contract runs a full year —
        // Billing Cycle and Contract Length are deliberately different here.
        $services = [
            ['service_code' => 'IT-AMC01', 'name' => 'Server Maintenance AMC',       'description' => 'Quarterly-billed annual maintenance contract for servers', 'rate' => 18000, 'tax_percent' => 18, 'hsn' => '998314', 'unit' => 'quarter', 'billing_cycle' => 'quarterly', 'duration_value' => 12, 'duration_unit' => 'months'],
            ['service_code' => 'IT-SEC01', 'name' => 'Network Security Audit',       'description' => 'One-time vulnerability assessment and penetration test',    'rate' => 35000, 'tax_percent' => 18, 'hsn' => '998314', 'unit' => 'project', 'billing_cycle' => 'one_time',  'duration_value' => null, 'duration_unit' => null],
            ['service_code' => 'IT-BKP01', 'name' => 'Cloud Backup Service',         'description' => 'Automated offsite backup, billed monthly',                  'rate' => 2500,  'tax_percent' => 18, 'hsn' => '998319', 'unit' => 'month',   'billing_cycle' => 'monthly',   'duration_value' => null, 'duration_unit' => null],
            ['service_code' => 'IT-HLP01', 'name' => 'IT Helpdesk Support',          'description' => 'Monthly-billed helpdesk, 6-month minimum commitment',       'rate' => 9000,  'tax_percent' => 18, 'hsn' => '998313', 'unit' => 'month',   'billing_cycle' => 'monthly',   'duration_value' => 6,  'duration_unit' => 'months'],
            ['service_code' => 'IT-LIC01', 'name' => 'Software License Renewal',     'description' => 'Annual license renewal for business software suite',        'rate' => 45000, 'tax_percent' => 18, 'hsn' => '998434', 'unit' => 'year',    'billing_cycle' => 'yearly',    'duration_value' => 12, 'duration_unit' => 'months'],
        ];

        foreach ($services as $s) {
            Service::create(array_merge($s, [
                'tenant_id' => $tenant->id,
                'is_active' => true,
            ]));
        }

        $this->command->info("✓ " . count($services) . " IT/AMC services added for tenant: {$tenant->name}");
    }
}
