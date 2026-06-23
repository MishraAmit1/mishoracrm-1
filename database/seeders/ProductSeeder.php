<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();
        if (!$tenant) {
            $this->command->error('No tenant found. Create a tenant first.');
            return;
        }

        $products = [
            ['name' => 'Web Design Service',         'description' => 'Custom website design and development',          'rate' => 15000,  'tax_percent' => 18, 'hsn' => '998314', 'unit' => 'project'],
            ['name' => 'SEO Package',                'description' => 'Monthly SEO optimization and reporting',          'rate' => 8000,   'tax_percent' => 18, 'hsn' => '998361', 'unit' => 'month'],
            ['name' => 'Logo Design',                'description' => 'Professional logo design with 3 revisions',       'rate' => 5000,   'tax_percent' => 18, 'hsn' => '998383', 'unit' => 'project'],
            ['name' => 'Social Media Management',    'description' => 'Managing Facebook, Instagram, LinkedIn pages',    'rate' => 12000,  'tax_percent' => 18, 'hsn' => '998361', 'unit' => 'month'],
            ['name' => 'Mobile App Development',     'description' => 'Android/iOS app development',                     'rate' => 50000,  'tax_percent' => 18, 'hsn' => '998314', 'unit' => 'project'],
            ['name' => 'Domain Registration',        'description' => '1 year domain name registration',                 'rate' => 999,    'tax_percent' => 18, 'hsn' => '998431', 'unit' => 'year'],
            ['name' => 'Web Hosting (Annual)',        'description' => 'cPanel shared hosting - 1 year',                 'rate' => 3500,   'tax_percent' => 18, 'hsn' => '998431', 'unit' => 'year'],
            ['name' => 'Content Writing',            'description' => 'Blog/article writing per page',                   'rate' => 1500,   'tax_percent' => 12, 'hsn' => '998391', 'unit' => 'page'],
            ['name' => 'Laptop (HP EliteBook)',      'description' => 'HP EliteBook 840 G9, i5 12th Gen, 16GB RAM',     'rate' => 75000,  'tax_percent' => 12, 'hsn' => '847130', 'unit' => 'pcs'],
            ['name' => 'Annual Maintenance Contract','description' => 'AMC for website and server support',              'rate' => 24000,  'tax_percent' => 18, 'hsn' => '998314', 'unit' => 'year'],
        ];

        foreach ($products as $p) {
            Product::create(array_merge($p, [
                'tenant_id' => $tenant->id,
                'is_active' => true,
            ]));
        }

        $this->command->info("✓ " . count($products) . " products added for tenant: {$tenant->name}");
    }
}
