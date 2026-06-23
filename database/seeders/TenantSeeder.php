<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $plan = Plan::where('slug', 'pro')->first();

        $tenant = Tenant::create([
            'name'      => 'Octa Core Technologies',
            'subdomain' => 'octacore',
            'email'     => 'octacoretechnologies0@gmail.com',
            'phone'     => '9876543210',
            'timezone'  => 'Asia/Kolkata',
            'currency'  => 'INR',
            'status'    => 'active',
        ]);

        if ($plan) {
            Subscription::create([
                'tenant_id'     => $tenant->id,
                'plan_id'       => $plan->id,
                'status'        => 'active',
                'billing_cycle' => 'yearly',
                'started_at'    => now(),
                'ends_at'       => now()->addYear(),
            ]);
        }

        $user = User::create([
            'tenant_id'         => $tenant->id,
            'name'              => 'Admin',
            'email'             => 'octacoretechnologies0@gmail.com',
            'password'          => Hash::make('Admin@12345'),
            'user_type'         => 'tenant_admin',
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);
        $user->assignRole('tenant_admin');

        $this->command->info("✓ Tenant: {$tenant->name} (ID: {$tenant->id})");
        $this->command->info("✓ User: {$user->email} / Admin@12345");
    }
}
