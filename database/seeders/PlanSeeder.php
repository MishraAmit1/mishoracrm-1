<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // PlanSeeder
        Plan::insert([
            [
                'name'          => 'Free Trial',
                'slug'          => 'free',
                'monthly_price' => 0,
                'yearly_price'  => 0,
                'features'      => json_encode([
                    'leads'     => 50,
                    'users'     => 2,
                    'whatsapp'  => false,
                    'reports'   => false,
                ]),
                'sort_order'    => 0,
            ],
            [
                'name'          => 'Starter',
                'slug'          => 'starter',
                'monthly_price' => 999,
                'yearly_price'  => 9999,
                'features'      => json_encode([
                    'leads'     => 500,
                    'users'     => 5,
                    'whatsapp'  => true,
                    'reports'   => true,
                ]),
                'sort_order'    => 1,
            ],
            [
                'name'          => 'Pro',
                'slug'          => 'pro',
                'monthly_price' => 2499,
                'yearly_price'  => 24999,
                'features'      => json_encode([
                    'leads'     => -1,       // unlimited
                    'users'     => -1,
                    'whatsapp'  => true,
                    'reports'   => true,
                    'social_leads' => true,
                ]),
                'sort_order'    => 2,
            ],
        ]);
    }
}
