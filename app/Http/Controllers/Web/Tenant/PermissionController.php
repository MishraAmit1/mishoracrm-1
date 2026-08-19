<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionController extends Controller
{
    // ── Add-only permission creation (tenant_admin) ─────────────────
    // Naya module ke liye tenant admin khud permission add kar sakta hai —
    // developer ko seeder edit karne ki zarurat nahi.
    // Rename/delete jaan-boojh kar yahan nahi diya — tenant_admin role
    // poore platform me shared hai, isliye wo actions Super Admin ke
    // paas hi rakhe gaye hain taaki ek tenant doosre tenants ka access na tode.

    public function create(): View
    {
        $modules = $this->existingModules();

        return view('tenant.permissions.create', compact('modules'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'module' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/'],
            'action' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/'],
        ], [
            'module.regex' => 'Module can only contain lowercase letters, numbers, and underscores.',
            'action.regex' => 'Action can only contain lowercase letters, numbers, and underscores.',
        ]);

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
            ->route('tenant.roles.index')
            ->with('success', "Permission '{$name}' created. You can now assign it to any role.");
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
