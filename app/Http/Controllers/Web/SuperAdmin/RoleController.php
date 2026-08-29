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
    // ── Shared/default role permissions ──────────────────────────
    // "tenant_admin" aur "staff" dono poore platform me shared roles hain
    // (naye tenants inhe as-is inherit karte hain). Inke default permissions
    // sirf Super Admin change kar sakta hai. Tenants apni copy bana kar
    // ("tenant_{id}_staff") khud customise kar sakte hain.

    private const EDITABLE = [
        'tenant_admin' => [
            'title' => 'Tenant Admin Role',
            'sub'   => 'Controls what every Tenant Admin can access across all tenants.',
        ],
        'staff' => [
            'title' => 'Default Staff Role',
            'sub'   => 'The starting permission set every new tenant\'s Staff role inherits. Tenants can customise their own copy.',
        ],
    ];

    public function edit(string $role): View
    {
        abort_unless(array_key_exists($role, self::EDITABLE), 404);

        $roleModel = Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        $roleModel->load('permissions');

        $permissions = Permission::orderBy('name')->get()
            ->groupBy(fn ($p) => explode('.', $p->name)[0] ?? 'other');

        $rolePermIds = $roleModel->permissions->pluck('id')->toArray();
        $meta        = self::EDITABLE[$role];

        return view('superadmin.roles.edit', [
            'role'        => $roleModel,
            'roleKey'     => $role,
            'permissions' => $permissions,
            'rolePermIds' => $rolePermIds,
            'pageTitle'   => $meta['title'],
            'pageSub'     => $meta['sub'],
        ]);
    }

    public function update(Request $request, string $role): RedirectResponse
    {
        abort_unless(array_key_exists($role, self::EDITABLE), 404);

        $request->validate([
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $roleModel = Role::where('name', $role)->firstOrFail();

        $perms = $request->filled('permissions')
            ? Permission::whereIn('id', $request->permissions)->get()
            : collect();

        $roleModel->syncPermissions($perms);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('superadmin.roles.edit', $role)
            ->with('success', self::EDITABLE[$role]['title'] . ' permissions updated.');
    }

    // ── Backward-compatible aliases ──────────────────────────────
    public function editTenantAdmin(): View
    {
        return $this->edit('tenant_admin');
    }

    public function updateTenantAdmin(Request $request): RedirectResponse
    {
        return $this->update($request, 'tenant_admin');
    }
}
