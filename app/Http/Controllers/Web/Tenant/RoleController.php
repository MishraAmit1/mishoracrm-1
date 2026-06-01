<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    // ── Sirf tenant ke roles ──────────────────────────────────────
    // Naming convention: "tenant_{id}_{role_name}"
    // e.g. "tenant_1_sales_manager"
    //
    // Tenant admin sirf apne tenant ke roles manage kar sakta hai
    // superadmin aur tenant_admin roles ko edit/delete nahi kar sakta

    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    private function tenantRolePrefix(): string
    {
        return 'tenant_' . $this->tenantId() . '_';
    }

    // Sirf is tenant ke roles
    private function getTenantRoles()
    {
        return Role::where('name', 'like', $this->tenantRolePrefix() . '%')
            ->withCount('users')
            ->get();
    }

    // System roles jo tenant admin ko dikhne chahiye (read-only)
    private function getSystemRoles(): array
    {
        return ['tenant_admin', 'staff'];
    }

    // Sab permissions grouped by module
    private function getAllPermissions()
    {
        return Permission::all()
            ->groupBy(fn($p) => explode('.', $p->name)[0] ?? 'other');
    }

    // ── Index ─────────────────────────────────────────────────────
    public function index(): View
    {
        $tenantRoles = $this->getTenantRoles();
        $systemRoles = Role::whereIn('name', ['tenant_admin', 'staff'])
            ->withCount('users')
            ->get();

        return view('tenant.roles.index', compact(
            'tenantRoles',
            'systemRoles'
        ));
    }

    // ── Create ────────────────────────────────────────────────────
    public function create(): View
    {
        $permissions = $this->getAllPermissions();
        $copyFrom    = Role::whereIn('name', ['tenant_admin', 'staff'])
            ->orWhere('name', 'like', $this->tenantRolePrefix() . '%')
            ->get();

        return view('tenant.roles.create', compact('permissions', 'copyFrom'));
    }

    // ── Store ─────────────────────────────────────────────────────
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'            => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/'],
            'description'     => ['nullable', 'string', 'max:255'],
            'permissions'     => ['nullable', 'array'],
            'permissions.*'   => ['exists:permissions,id'],
        ], [
            'name.regex' => 'Role name can only contain lowercase letters, numbers, and underscores.',
        ]);

        $fullName = $this->tenantRolePrefix() . $request->name;

        // Duplicate check
        if (Role::where('name', $fullName)->exists()) {
            return back()
                ->withInput()
                ->withErrors(['name' => 'This role name already exists.']);
        }

        $role = Role::create([
            'name'        => $fullName,
            'guard_name'  => 'web',
            'description' => $request->description,
        ]);

        // Assign permissions
        if ($request->filled('permissions')) {
            $perms = Permission::whereIn('id', $request->permissions)->get();
            $role->syncPermissions($perms);
        }

        return redirect()
            ->route('tenant.roles.index')
            ->with('success', "Role '{$request->name}' created successfully.");
    }

    // ── Show ──────────────────────────────────────────────────────
    public function show(int $id): View
    {
        $role = $this->findRole($id);
        $role->load('permissions');

        $permissions = $this->getAllPermissions();
        $users       = User::role($role->name)
            ->where('tenant_id', $this->tenantId())
            ->get(['id', 'name', 'email']);

        $roleDisplayName = $this->displayName($role->name);

        return view('tenant.roles.show', compact(
            'role', 'permissions', 'users', 'roleDisplayName'
        ));
    }

    // ── Edit ──────────────────────────────────────────────────────
    public function edit(int $id): View
    {
        $role = $this->findRole($id);
        $role->load('permissions');

        $permissions     = $this->getAllPermissions();
        $rolePermIds     = $role->permissions->pluck('id')->toArray();
        $roleDisplayName = $this->displayName($role->name);

        return view('tenant.roles.edit', compact(
            'role', 'permissions', 'rolePermIds', 'roleDisplayName'
        ));
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(Request $request, int $id): RedirectResponse
    {
        $role = $this->findRole($id);

        $request->validate([
            'description'   => ['nullable', 'string', 'max:255'],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        // Description update
        $role->update(['description' => $request->description]);

        // Permissions sync
        $perms = $request->filled('permissions')
            ? Permission::whereIn('id', $request->permissions)->get()
            : collect();

        $role->syncPermissions($perms);

        return redirect()
            ->route('tenant.roles.show', $id)
            ->with('success', 'Role permissions updated.');
    }

    // ── Destroy ───────────────────────────────────────────────────
    public function destroy(int $id): RedirectResponse
    {
        $role = $this->findRole($id);

        // Check if any users have this role
        $userCount = User::role($role->name)
            ->where('tenant_id', $this->tenantId())
            ->count();

        if ($userCount > 0) {
            return back()->with('error',
                "Cannot delete role '{$this->displayName($role->name)}' — {$userCount} staff member(s) are assigned to it."
            );
        }

        $name = $this->displayName($role->name);
        $role->delete();

        return redirect()
            ->route('tenant.roles.index')
            ->with('success', "Role '{$name}' deleted.");
    }

    // ── Assign role to user ───────────────────────────────────────
    public function assignToUser(Request $request): RedirectResponse
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'role'    => ['required', 'exists:roles,name'],
        ]);

        $user = User::where('id', $request->user_id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();

        // Only tenant roles or base staff role
        $allowed = array_merge(
            ['staff'],
            Role::where('name', 'like', $this->tenantRolePrefix() . '%')
                ->pluck('name')->toArray()
        );

        if (!in_array($request->role, $allowed)) {
            return back()->with('error', 'Invalid role assignment.');
        }

        $user->syncRoles([$request->role]);

        return back()->with('success', "Role updated for {$user->name}.");
    }

    // ── Helpers ───────────────────────────────────────────────────
    private function findRole(int $id): Role
    {
        $role = Role::where('id', $id)->firstOrFail();

        // Must belong to this tenant
        if (!str_starts_with($role->name, $this->tenantRolePrefix())) {
            abort(403, 'You cannot edit this role.');
        }

        return $role;
    }

    // "tenant_1_sales_manager" → "Sales Manager"
    private function displayName(string $roleName): string
    {
        $name = preg_replace('/^tenant_\d+_/', '', $roleName);
        return ucwords(str_replace('_', ' ', $name));
    }
}