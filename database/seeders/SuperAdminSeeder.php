<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Super Admin User Create / Update
        $user = User::updateOrCreate(
            [
                'email' => 'admin@saascrm.com',
            ],
            [
                'tenant_id' => null,
                'name' => 'Super Admin',
                'password' => Hash::make('Admin@12345'),
                'phone' => '9999999999',
                'user_type' => 'superadmin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Assign Role
        if (!$user->hasRole('superadmin')) {
            $user->assignRole('superadmin');
        }

        $this->command->info('✅ Super Admin created successfully.');
        $this->command->line('Email: admin@saascrm.com');
        $this->command->line('Password: Admin@12345');
    }
    
}
