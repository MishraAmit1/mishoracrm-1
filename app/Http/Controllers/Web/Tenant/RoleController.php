<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Helpers\Roles;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    // ── Sirf tenant ke roles ──────────────────────────────────────
    // Naming convention: "tenant_{id}_{role_name}"  e.g. "tenant_1_sales_manager"
    //
    // Tenant admin sirf apne tenant ke roles manage kar sakta hai.
    // - "tenant_admin" role poore platform me shared hai -> sirf Super Admin edit kar sakta hai (yahan read-only).
    // - "staff" role ek shared template hai -> pehli baar edit karne par tenant ki apni copy
    //   ("tenant_{id}_staff") ban jaati hai (copy-on-write, dekho App\Helpers\Roles).

    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    private function tenantRolePrefix(): string
    {
        return Roles::tenantPrefix($this->tenantId());
    }

    /** System-copy role names for this tenant (shown in the System section, not Custom). */
    private function systemCopyNames(): array
    {
        return [
            $this->tenantRolePrefix() . 'staff',
            $this->tenantRolePrefix() . 'admin',
        ];
    }

    /** Sirf is tenant ke custom roles (system copies chhod kar). */
    private function getTenantRoles()
    {
        return Role::where('name', 'like', $this->tenantRolePrefix() . '%')
            ->whereNotIn('name', $this->systemCopyNames())
            ->withCount('users')
            ->orderBy('name')
            ->get();
    }

    /** Sab permissions module ke hisaab se grouped. */
    private function getAllPermissions()
    {
        return Permission::orderBy('name')->get()
            ->groupBy(fn ($p) => explode('.', $p->name)[0] ?? 'other');
    }

    // ── Index ─────────────────────────────────────────────────────
    public function index(): View
    {
        $tenantRoles = $this->getTenantRoles();

        $systemRoles = collect([
            $this->systemRoleCard('tenant_admin', 'Admin', 'Full access to every module and setting'),
            $this->systemRoleCard('staff', 'Staff', 'Standard access for team members'),
        ])->filter()->values();

        return view('tenant.roles.index', compact('tenantRoles', 'systemRoles'));
    }

    private function systemRoleCard(string $base, string $label, string $fallbackDesc): ?object
    {
        $role = Roles::effectiveSystemRole($this->tenantId(), $base);
        if (! $role) {
            return null;
        }

        $isCopy = str_starts_with($role->name, $this->tenantRolePrefix());

        return (object) [
            'id'          => $role->id,
            'name'        => $role->name,
            'base'        => $base === 'tenant_admin' ? 'admin' : $base,
            'label'       => $label,
            'description' => $role->description ?: $fallbackDesc,
            'perm_count'  => $role->permissions()->count(),
            'user_count'  => User::role($role->name)->where('tenant_id', $this->tenantId())->count(),
            'editable'    => $isCopy,       // only the tenant's own copy is editable here
            'customised'  => $isCopy,
        ];
    }

    // ── Create ────────────────────────────────────────────────────
    public function create(): View
    {
        $permissions = $this->getAllPermissions();

        $copyFrom = collect([
            Roles::effectiveAdminRole($this->tenantId()),
            Roles::effectiveStaffRole($this->tenantId()),
        ])->filter()->merge(
            Role::where('name', 'like', $this->tenantRolePrefix() . '%')
                ->whereNotIn('name', $this->systemCopyNames())
                ->get()
        )->unique('id')->values();

        return view('tenant.roles.create', compact('permissions', 'copyFrom'));
    }

    // ── Store ─────────────────────────────────────────────────────
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'          => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/'],
            'description'   => ['nullable', 'string', 'max:255'],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ], [
            'name.regex' => 'Role name can only contain lowercase letters, numbers, and underscores.',
        ]);

        // "staff" / "admin" jaise reserved slug custom role ke liye block karo.
        if (Roles::isSystem($request->name) || in_array($request->name, ['admin', 'superadmin'], true)) {
            return back()->withInput()->withErrors([
                'name' => 'This name is reserved. Please choose a different one.',
            ]);
        }

        $fullName = $this->tenantRolePrefix() . $request->name;

        if (Role::where('name', $fullName)->exists()) {
            return back()->withInput()->withErrors(['name' => 'This role name already exists.']);
        }

        $role = Role::create([
            'name'        => $fullName,
            'guard_name'  => 'web',
            'description' => $request->description,
        ]);

        if ($request->filled('permissions')) {
            $role->syncPermissions(Permission::whereIn('id', $request->permissions)->get());
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('tenant.roles.index')
            ->with('success', "Role '" . Roles::label($fullName) . "' created successfully.");
    }

    // ── Show ──────────────────────────────────────────────────────
    public function show(int $id): View
    {
        $role = $this->findViewableRole($id);
        $role->load('permissions');

        $permissions     = $this->getAllPermissions();
        $users           = User::role($role->name)
            ->where('tenant_id', $this->tenantId())
            ->get(['id', 'name', 'email']);
        $roleDisplayName = Roles::label($role->name);
        $editable        = $this->isEditable($role);

        // Staff not already on this role (for the "assign to this role" picker).
        $assignedIds     = $users->pluck('id');
        $addableStaff    = User::where('tenant_id', $this->tenantId())
            ->where('is_active', true)
            ->whereNotIn('id', $assignedIds)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        // Other roles this role's users could be moved to (for delete/reassign).
        $otherRoles = Roles::assignableFor($this->tenantId())
            ->reject(fn ($r) => $r['name'] === $role->name)
            ->values();

        return view('tenant.roles.show', compact(
            'role', 'permissions', 'users', 'roleDisplayName',
            'editable', 'addableStaff', 'otherRoles'
        ));
    }

    // ── Customise a shared system role (copy-on-write) ─────────────
    public function customiseSystem(string $base): RedirectResponse
    {
        // Route param is "admin" | "staff"; helper wants the real base name.
        $realBase = $base === 'admin' ? 'tenant_admin' : $base;

        $clone = Roles::customiseSystemRole($this->tenantId(), $realBase);

        return redirect()
            ->route('tenant.roles.edit', $clone->id)
            ->with('success', 'You now have your own copy of the ' . Roles::label($realBase) . ' role. Adjust its permissions below.');
    }

    // ── Edit ──────────────────────────────────────────────────────
    public function edit(int $id): View
    {
        $role = $this->findRole($id);
        $role->load('permissions');

        $permissions     = $this->getAllPermissions();
        $rolePermIds     = $role->permissions->pluck('id')->toArray();
        $roleDisplayName = Roles::label($role->name);

        return view('tenant.roles.edit', compact('role', 'permissions', 'rolePermIds', 'roleDisplayName'));
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

        // "staff" copy ka description auto-set rakho warna user ka diya hua.
        $role->update(['description' => $request->description ?: $role->description]);

        $perms = $request->filled('permissions')
            ? Permission::whereIn('id', $request->permissions)->get()
            : collect();

        $role->syncPermissions($perms);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('tenant.roles.show', $role->id)
            ->with('success', 'Role permissions updated.');
    }

    // ── Destroy (with reassign of any members) ────────────────────
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $role = $this->findRole($id);
        $base = Roles::systemBase($role->name);   // 'staff' | 'tenant_admin' | null

        $members = User::where('tenant_id', $this->tenantId())
            ->whereHas('roles', fn ($q) => $q->where('name', $role->name))
            ->get();

        $target = null;

        if ($members->isNotEmpty()) {
            if ($base) {
                // A customised system copy — members simply fall back to the shared template.
                $target = $base;
            } else {
                $allowed = Roles::assignableFor($this->tenantId())
                    ->pluck('name')
                    ->reject(fn ($n) => $n === $role->name)
                    ->values()->all();

                $request->validate(
                    ['reassign_to' => ['required', Rule::in($allowed)]],
                    ['reassign_to.required' => 'Choose a role to move the current members to.']
                );
                $target = $request->reassign_to;
            }

            $members->each(function (User $u) use ($target) {
                $u->syncRoles([$target]);
                $u->update(['user_type' => Roles::userTypeFor($target)]);
            });
        }

        $name = Roles::label($role->name);
        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if ($base) {
            $msg = "Your custom {$name} role was removed — its "
                . ($members->isNotEmpty() ? $members->count() . ' member(s) now use' : 'members will use')
                . " the default {$name} permissions again.";
        } elseif ($members->isNotEmpty()) {
            $msg = "Role '{$name}' deleted. {$members->count()} member(s) moved to '" . Roles::label($target) . "'.";
        } else {
            $msg = "Role '{$name}' deleted.";
        }

        return redirect()->route('tenant.roles.index')->with('success', $msg);
    }

    // ── Role permissions (AJAX, for "copy from") ──────────────────
    public function permissions(int $id): JsonResponse
    {
        $role = $this->findViewableRole($id);

        return response()->json([
            'permission_ids' => $role->permissions->pluck('id'),
        ]);
    }

    // ── Assign role to a specific user ────────────────────────────
    public function assignToUser(Request $request): RedirectResponse
    {
        $assignable = Roles::assignableFor($this->tenantId());

        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'role'    => ['required', Rule::in($assignable->pluck('name'))],
        ]);

        $user = User::where('id', $request->user_id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();

        $user->syncRoles([$request->role]);
        $user->update(['user_type' => Roles::userTypeFor($request->role)]);

        return back()->with('success', "Role updated for {$user->name}.");
    }

    // ── Helpers ───────────────────────────────────────────────────

    /** Editable roles = is tenant ke prefixed roles (custom + staff copy). */
    private function findRole(int $id): Role
    {
        $role = Role::findOrFail($id);

        if (! str_starts_with($role->name, $this->tenantRolePrefix())) {
            abort(403, 'You cannot edit this role. System roles are managed by the platform.');
        }

        return $role;
    }

    /** Viewable = editable + shared system roles (read-only view). */
    private function findViewableRole(int $id): Role
    {
        $role = Role::findOrFail($id);

        if (str_starts_with($role->name, $this->tenantRolePrefix())
            || in_array($role->name, ['tenant_admin', 'staff'], true)) {
            return $role;
        }

        abort(403, 'This role belongs to another workspace.');
    }

    private function isEditable(Role $role): bool
    {
        return str_starts_with($role->name, $this->tenantRolePrefix());
    }
}
