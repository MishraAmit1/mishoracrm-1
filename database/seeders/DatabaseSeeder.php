<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);


        $this->call([
        // RolesAndPermissionsSeeder::class,  
        // PlanSeeder::class,                 // phir plans
         SuperAdminSeeder::class,           // phir superadmin user
         MessageTemplateSeeder::class,      // phir message templates
        ]);
    }
}
