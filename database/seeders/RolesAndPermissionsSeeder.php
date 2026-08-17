<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // ── Reset cached roles/permissions ────────────────────────
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ─────────────────────────────────────────────────────────
        // PERMISSIONS — Developer define karta hai
        // Format: "module.action"
        // Tenant admin sirf inhe roles ko assign kar sakta hai
        // Naye permissions add karna = developer ka kaam
        // ─────────────────────────────────────────────────────────
        $permissions = [

            // ── Leads ─────────────────────────────────────────────
            'leads.view_own',         // Sirf apne assigned leads
            'leads.view_all',         // Sab tenant ke leads
            'leads.create',
            'leads.edit_own',         // Sirf apne assigned leads edit
            'leads.edit_all',         // Koi bhi lead edit karo
            'leads.delete',
            'leads.assign',           // Kisi bhi staff ko assign karo
            'leads.convert',          // Lead ko contact mein convert karo
            'leads.export',
            'leads.import',
            'leads.merge',            // Duplicate leads merge karo

            // ── Contacts ─────────────────────────────────────────
            'contacts.view_own',
            'contacts.view_all',
            'contacts.create',
            'contacts.edit_own',
            'contacts.edit_all',
            'contacts.delete',
            'contacts.export',
            'contacts.import',
            'contacts.merge',         // Duplicate contacts merge karo

            // ── Deals ─────────────────────────────────────────────
            'deals.view_own',
            'deals.view_all',
            'deals.create',
            'deals.edit_own',
            'deals.edit_all',
            'deals.delete',
            'deals.export',

            // ── Follow-ups ────────────────────────────────────────
            'followups.view_own',
            'followups.view_all',
            'followups.create',
            'followups.edit_own',
            'followups.edit_all',
            'followups.delete',

            // ── Tasks ─────────────────────────────────────────────
            'tasks.view_own',
            'tasks.view_all',
            'tasks.create',
            'tasks.edit_own',
            'tasks.edit_all',
            'tasks.delete',

            // ── Quotations ────────────────────────────────────────
            'quotations.view_own',
            'quotations.view_all',
            'quotations.create',
            'quotations.edit',
            'quotations.delete',
            'quotations.send',
            'quotations.export',

            // ── Invoices ─────────────────────────────────────────
            'invoices.view_own',
            'invoices.view_all',
            'invoices.create',
            'invoices.edit',
            'invoices.delete',
            'invoices.send',
            'invoices.record_payment',
            'invoices.export',

            // ── Vendors ───────────────────────────────────────────
            'vendors.view',
            'vendors.create',
            'vendors.edit',
            'vendors.delete',

            // ── Purchase Requests ─────────────────────────────────
            'purchase_requests.view_own',
            'purchase_requests.view_all',
            'purchase_requests.create',
            'purchase_requests.edit',
            'purchase_requests.approve',
            'purchase_requests.delete',

            // ── Purchase Orders ───────────────────────────────────
            'purchase_orders.view_own',
            'purchase_orders.view_all',
            'purchase_orders.create',
            'purchase_orders.edit',
            'purchase_orders.delete',
            'purchase_orders.send',
            'purchase_orders.receive',
            'purchase_orders.export',

            // ── Staff ─────────────────────────────────────────────
            'staff.view',
            'staff.create',
            'staff.edit',
            'staff.delete',
            'staff.activate_deactivate',

            // ── Departments ───────────────────────────────────────
            'departments.view',
            'departments.create',
            'departments.edit',
            'departments.delete',

            // ── WhatsApp ──────────────────────────────────────────
            'whatsapp.send',
            'whatsapp.bulk_send',
            'whatsapp.view_logs',
            'whatsapp.manage_templates',

            // ── Email ─────────────────────────────────────────────
            'email.send',
            'email.bulk_send',
            'email.view_logs',
            'email.manage_templates',

            // ── Reports ───────────────────────────────────────────
            'reports.view_basic',     // Own stats
            'reports.view_all',       // Full team reports
            'reports.export',

            // ── Settings ─────────────────────────────────────────
            'settings.company',       // Company info, logo
            'settings.billing',       // Plan & subscription
            'settings.custom_fields', // Custom field config
            'settings.roles',         // Roles management (view only for non-admin)

            // ── Notifications ─────────────────────────────────────
            'notifications.view',
            'notifications.manage_preferences',

            // ── Audit Logs ────────────────────────────────────────
            'audit_logs.view',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // ─────────────────────────────────────────────────────────
        // SYSTEM ROLES — Fixed, not deletable by tenant
        // ─────────────────────────────────────────────────────────

        // SuperAdmin — everything (no permission check)
        $superadmin = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);

        // Tenant Admin — everything within their tenant
        $tenantAdmin = Role::firstOrCreate(['name' => 'tenant_admin', 'guard_name' => 'web']);
        $tenantAdmin->syncPermissions(Permission::all());

        // Staff — basic access (tenant admin can create custom roles for more)
        $staff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $staff->syncPermissions([
            'leads.view_own',
            'leads.create',
            'leads.edit_own',
            'contacts.view_own',
            'contacts.create',
            'contacts.edit_own',
            'deals.view_own',
            'followups.view_own',
            'followups.create',
            'followups.edit_own',
            'tasks.view_own',
            'tasks.create',
            'tasks.edit_own',
            'purchase_requests.view_own',
            'purchase_requests.create',
            'whatsapp.send',
            'email.send',
            'reports.view_basic',
            'notifications.view',
            'notifications.manage_preferences',
        ]);

        $this->command->info('✅ Permissions seeded: ' . Permission::count());
        $this->command->info('✅ Roles seeded: superadmin, tenant_admin, staff');
    }
}