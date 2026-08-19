<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    // ── Tenant Admin role permissions ────────────────────────────
    // tenant_admin ek hi shared role hai poore platform me (sab tenants
    // ke admin isi role ko use karte hain), isliye iske permissions
    // sirf Super Admin change kar sakta hai — tenant admin khud nahi.

    public function editTenantAdmin(): View
    {
        $role = Role::firstOrCreate(['name' => 'tenant_admin', 'guard_name' => 'web']);
        $role->load('permissions');

        $permissions = Permission::all()
            ->groupBy(fn($p) => explode('.', $p->name)[0] ?? 'other');

        $rolePermIds = $role->permissions->pluck('id')->toArray();

        return view('superadmin.roles.tenant-admin-edit', compact('role', 'permissions', 'rolePermIds'));
    }

    public function updateTenantAdmin(Request $request): RedirectResponse
    {
        $request->validate([
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role = Role::where('name', 'tenant_admin')->firstOrFail();

        $perms = $request->filled('permissions')
            ? Permission::whereIn('id', $request->permissions)->get()
            : collect();

        $role->syncPermissions($perms);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('superadmin.roles.tenant-admin.edit')
            ->with('success', 'Tenant Admin role permissions updated.');
    }
}
