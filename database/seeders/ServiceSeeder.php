<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();
        if (!$tenant) {
            $this->command->error('No tenant found. Create a tenant first.');
            return;
        }

        $services = [
            ['service_code' => 'SRV-WD001', 'name' => 'Web Design Service',          'description' => 'Custom website design and development',       'rate' => 15000, 'tax_percent' => 18, 'hsn' => '998314', 'unit' => 'project', 'billing_cycle' => 'one_time', 'duration_value' => null, 'duration_unit' => null],
            ['service_code' => 'SRV-SEO01', 'name' => 'SEO Package',                 'description' => 'Monthly SEO optimization and reporting',       'rate' => 8000,  'tax_percent' => 18, 'hsn' => '998361', 'unit' => 'month',   'billing_cycle' => 'monthly',  'duration_value' => 1,  'duration_unit' => 'months'],
            ['service_code' => 'SRV-LGO01', 'name' => 'Logo Design',                 'description' => 'Professional logo design with 3 revisions',    'rate' => 5000,  'tax_percent' => 18, 'hsn' => '998383', 'unit' => 'project', 'billing_cycle' => 'one_time', 'duration_value' => null, 'duration_unit' => null],
            ['service_code' => 'SRV-SMM01', 'name' => 'Social Media Management',     'description' => 'Managing Facebook, Instagram, LinkedIn pages', 'rate' => 12000, 'tax_percent' => 18, 'hsn' => '998361', 'unit' => 'month',   'billing_cycle' => 'monthly',  'duration_value' => 1,  'duration_unit' => 'months'],
            ['service_code' => 'SRV-APP01', 'name' => 'Mobile App Development',      'description' => 'Android/iOS app development',                  'rate' => 50000, 'tax_percent' => 18, 'hsn' => '998314', 'unit' => 'project', 'billing_cycle' => 'one_time', 'duration_value' => null, 'duration_unit' => null],
            ['service_code' => 'SRV-CW001', 'name' => 'Content Writing',             'description' => 'Blog/article writing per page',                'rate' => 1500,  'tax_percent' => 12, 'hsn' => '998391', 'unit' => 'page',    'billing_cycle' => 'one_time', 'duration_value' => null, 'duration_unit' => null],
            ['service_code' => 'SRV-AMC01', 'name' => 'Annual Maintenance Contract', 'description' => 'AMC for website and server support',           'rate' => 24000, 'tax_percent' => 18, 'hsn' => '998314', 'unit' => 'year',    'billing_cycle' => 'yearly',   'duration_value' => 12, 'duration_unit' => 'months'],
            ['service_code' => 'SRV-HST01', 'name' => 'Web Hosting (Managed)',       'description' => 'cPanel shared hosting - managed, 1 year',      'rate' => 3500,  'tax_percent' => 18, 'hsn' => '998431', 'unit' => 'year',    'billing_cycle' => 'yearly',   'duration_value' => 12, 'duration_unit' => 'months'],
        ];

        foreach ($services as $s) {
            Service::create(array_merge($s, [
                'tenant_id' => $tenant->id,
                'is_active' => true,
            ]));
        }

        $this->command->info("✓ " . count($services) . " services added for tenant: {$tenant->name}");
    }
}
