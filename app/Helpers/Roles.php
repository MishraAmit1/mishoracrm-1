<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Central helper for the tenant role model.
 *
 * Role types in this app:
 *   - superadmin            : platform owner (never assignable to staff)
 *   - tenant_admin          : shared "owner" role template, one row for the whole platform
 *   - staff                 : shared default template for new tenants
 *   - tenant_{id}_admin     : a tenant's own editable copy of "tenant_admin" (copy-on-write)
 *   - tenant_{id}_staff     : a tenant's own editable copy of "staff" (copy-on-write)
 *   - tenant_{id}_{slug}    : fully custom tenant roles
 *
 * Real authorisation runs off the users.user_type column
 * (superadmin | tenant_admin | staff), kept in sync with the assigned role.
 * The Spatie role itself is just the permission bundle.
 */
class Roles
{
    /** System roles a tenant can never rename/delete. */
    public const SYSTEM = ['superadmin', 'tenant_admin', 'staff'];

    /** System roles a tenant admin is allowed to customise (copy-on-write). */
    public const CUSTOMISABLE = ['staff', 'tenant_admin'];

    public static function tenantPrefix(int $tenantId): string
    {
        return "tenant_{$tenantId}_";
    }

    /** "tenant_1_sales_manager" -> "Sales Manager", "staff" -> "Staff". */
    public static function label(?string $roleName): string
    {
        if (! $roleName) {
            return '—';
        }

        $bare = preg_replace('/^tenant_\d+_/', '', $roleName);

        return match ($bare) {
            'tenant_admin', 'admin' => 'Admin',
            'staff'                 => 'Staff',
            default                 => ucwords(str_replace('_', ' ', $bare)),
        };
    }

    /** True for shared/system roles (with or without a tenant prefix). */
    public static function isSystem(string $roleName): bool
    {
        $bare = preg_replace('/^tenant_\d+_/', '', $roleName);

        return in_array($bare, ['superadmin', 'tenant_admin', 'admin', 'staff'], true);
    }

    /** The base name ("staff" | "tenant_admin") a customisable role maps to. */
    public static function systemBase(string $roleName): ?string
    {
        $bare = preg_replace('/^tenant_\d+_/', '', $roleName);

        return match ($bare) {
            'staff'                 => 'staff',
            'admin', 'tenant_admin' => 'tenant_admin',
            default                 => null,
        };
    }

    /** The Staff role that applies to a tenant: its own copy if any, else the shared template. */
    public static function effectiveStaffRole(int $tenantId): ?Role
    {
        return Role::where('name', self::tenantPrefix($tenantId) . 'staff')->first()
            ?? Role::where('name', 'staff')->first();
    }

    /** The Admin role that applies to a tenant: its own copy if any, else the shared template. */
    public static function effectiveAdminRole(int $tenantId): ?Role
    {
        return Role::where('name', self::tenantPrefix($tenantId) . 'admin')->first()
            ?? Role::where('name', 'tenant_admin')->first();
    }

    /** Resolve the effective role for a base name. */
    public static function effectiveSystemRole(int $tenantId, string $base): ?Role
    {
        return $base === 'tenant_admin'
            ? self::effectiveAdminRole($tenantId)
            : self::effectiveStaffRole($tenantId);
    }

    /**
     * Copy-on-write: ensure the tenant has its own editable copy of a shared
     * system role, migrating that tenant's users onto it. Returns the tenant role.
     */
    public static function customiseSystemRole(int $tenantId, string $base): Role
    {
        abort_unless(in_array($base, self::CUSTOMISABLE, true), 403, 'This role cannot be customised.');

        // "tenant_admin" -> "tenant_{id}_admin", "staff" -> "tenant_{id}_staff"
        $suffix    = $base === 'tenant_admin' ? 'admin' : $base;
        $cloneName = self::tenantPrefix($tenantId) . $suffix;

        if ($clone = Role::where('name', $cloneName)->first()) {
            return $clone;
        }

        $template = Role::where('name', $base)->firstOrFail();

        $clone = Role::create([
            'name'        => $cloneName,
            'guard_name'  => $template->guard_name,
            'description' => 'Your customised copy of the ' . self::label($base) . ' role.',
        ]);
        $clone->syncPermissions($template->permissions);

        // Move this tenant's users off the shared template and onto the copy.
        User::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereHas('roles', fn ($q) => $q->where('name', $base))
            ->get()
            ->each(function (User $u) use ($cloneName) {
                $u->syncRoles([$cloneName]);
                $u->update(['user_type' => self::userTypeFor($cloneName)]);
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $clone;
    }

    /**
     * Roles a tenant admin may assign to staff: Admin, Staff, then custom roles.
     *
     * @return Collection<int,array{name:string,label:string,description:?string,kind:string}>
     */
    public static function assignableFor(int $tenantId): Collection
    {
        $out    = collect();
        $prefix = self::tenantPrefix($tenantId);

        if ($admin = self::effectiveAdminRole($tenantId)) {
            $out->push([
                'name'        => $admin->name,
                'label'       => 'Admin',
                'description' => $admin->description ?: 'Full access to every module and setting',
                'kind'        => 'admin',
            ]);
        }

        if ($staff = self::effectiveStaffRole($tenantId)) {
            $out->push([
                'name'        => $staff->name,
                'label'       => 'Staff',
                'description' => $staff->description ?: 'Standard access for team members',
                'kind'        => 'staff',
            ]);
        }

        Role::where('name', 'like', $prefix . '%')
            ->whereNotIn('name', [$prefix . 'staff', $prefix . 'admin'])
            ->orderBy('name')
            ->get()
            ->each(function (Role $r) use ($out) {
                $out->push([
                    'name'        => $r->name,
                    'label'       => self::label($r->name),
                    'description' => $r->description,
                    'kind'        => 'custom',
                ]);
            });

        return $out->values();
    }

    /** The user_type column that should go with an assigned role name. */
    public static function userTypeFor(string $roleName): string
    {
        $bare = preg_replace('/^tenant_\d+_/', '', $roleName);

        return in_array($bare, ['tenant_admin', 'admin'], true) ? 'tenant_admin' : 'staff';
    }
}
