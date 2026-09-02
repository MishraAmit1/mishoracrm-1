<?php

namespace Database\Seeders;

use App\Models\Notification;
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
         RolesAndPermissionsSeeder::class,
        //  PlanSeeder::class,
         SuperAdminSeeder::class,
         MessageTemplateSeeder::class,
         NotificationSeeder::class,
         CustomFieldSeeder::class,
         GlobalFieldTemplateSeeder::class,
         LeadSeeder::class,
         DealSeeder::class,
         TaskSeeder::class,
         TaskTemplateSeeder::class,
        //  RolesAndPermissionsSeeder::class
        ]);
    }
}
