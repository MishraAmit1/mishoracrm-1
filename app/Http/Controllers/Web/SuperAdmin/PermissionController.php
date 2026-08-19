<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionController extends Controller
{
    // ── Master permissions list ──────────────────────────────────
    // Naya module add karne ke liye ab seeder edit karne ki zarurat nahi —
    // yahan se permission add karo, tenant_admin role ko automatically mil jayega.

    public function index(): View
    {
        $permissions = Permission::withCount('roles')
            ->orderBy('name')
            ->get()
            ->groupBy(fn($p) => explode('.', $p->name)[0] ?? 'other');

        return view('superadmin.permissions.index', compact('permissions'));
    }

    public function create(): View
    {
        $modules = $this->existingModules();

        return view('superadmin.permissions.create', compact('modules'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePermission($request);
        $name = $data['module'] . '.' . $data['action'];

        if (Permission::where('name', $name)->exists()) {
            return back()
                ->withInput()
                ->withErrors(['action' => 'This permission already exists.']);
        }

        $permission = Permission::create(['name' => $name, 'guard_name' => 'web']);

        Role::where('name', 'tenant_admin')->first()?->givePermissionTo($permission);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('superadmin.permissions.index')
            ->with('success', "Permission '{$name}' created and granted to Tenant Admin.");
    }

    public function edit(Permission $permission): View
    {
        [$module, $action] = array_pad(explode('.', $permission->name, 2), 2, '');
        $modules = $this->existingModules();

        return view('superadmin.permissions.edit', compact('permission', 'module', 'action', 'modules'));
    }

    public function update(Request $request, Permission $permission): RedirectResponse
    {
        $data = $this->validatePermission($request);
        $name = $data['module'] . '.' . $data['action'];

        if (Permission::where('name', $name)->where('id', '!=', $permission->id)->exists()) {
            return back()
                ->withInput()
                ->withErrors(['action' => 'This permission already exists.']);
        }

        $permission->update(['name' => $name]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('superadmin.permissions.index')
            ->with('success', "Permission renamed to '{$name}'.");
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        $name = $permission->name;
        $roleCount = $permission->roles()->count();

        $permission->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $message = "Permission '{$name}' deleted.";
        if ($roleCount > 0) {
            $message .= " Removed from {$roleCount} role(s).";
        }

        return redirect()
            ->route('superadmin.permissions.index')
            ->with('success', $message);
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function validatePermission(Request $request): array
    {
        return $request->validate([
            'module' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/'],
            'action' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/'],
        ], [
            'module.regex' => 'Module can only contain lowercase letters, numbers, and underscores.',
            'action.regex' => 'Action can only contain lowercase letters, numbers, and underscores.',
        ]);
    }

    private function existingModules()
    {
        return Permission::all()
            ->map(fn($p) => explode('.', $p->name)[0] ?? null)
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }
}
