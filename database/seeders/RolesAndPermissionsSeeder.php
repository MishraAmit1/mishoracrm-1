<?php


namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Permissions reset
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Leads
            'leads.view', 'leads.create', 'leads.edit', 'leads.delete',
            // Contacts
            'contacts.view', 'contacts.create', 'contacts.edit', 'contacts.delete',
            // Deals
            'deals.view', 'deals.create', 'deals.edit', 'deals.delete',
            // Tasks
            'tasks.view', 'tasks.create', 'tasks.edit', 'tasks.delete',
            // Invoices
            'invoices.view', 'invoices.create', 'invoices.edit',
            // Staff
            'staff.view', 'staff.create', 'staff.edit', 'staff.delete',
            // Reports
            'reports.view',
            // Settings
            'settings.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Roles banao
        $superAdmin = Role::firstOrCreate(['name' => 'superadmin']);
        $tenantAdmin = Role::firstOrCreate(['name' => 'tenant_admin']);
        $manager     = Role::firstOrCreate(['name' => 'manager']);
        $staff       = Role::firstOrCreate(['name' => 'staff']);

        // Permissions assign karo
        $tenantAdmin->givePermissionTo(Permission::all());

        $manager->givePermissionTo([
            'leads.view', 'leads.create', 'leads.edit',
            'contacts.view', 'contacts.create', 'contacts.edit',
            'deals.view', 'deals.create', 'deals.edit',
            'tasks.view', 'tasks.create', 'tasks.edit',
            'invoices.view', 'invoices.create',
            'staff.view', 'reports.view',
        ]);

        $staff->givePermissionTo([
            'leads.view', 'leads.create', 'leads.edit',
            'contacts.view', 'contacts.create',
            'deals.view', 'tasks.view', 'tasks.create',
        ]);
    }
}