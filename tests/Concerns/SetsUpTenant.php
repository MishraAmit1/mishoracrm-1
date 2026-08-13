<?php

namespace Tests\Concerns;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

/**
 * Shared "tenant + seeded permissions + role-holding user" boilerplate for
 * Lead module feature tests. Reuses the real RolesAndPermissionsSeeder so
 * tests stay in sync with the actual seeded permission set.
 */
trait SetsUpTenant
{
    protected function setUpTenant(): Tenant
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        return Tenant::factory()->create();
    }

    protected function makeUser(Tenant $tenant, string $role, array $permissions = []): User
    {
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'user_type' => in_array($role, ['superadmin', 'tenant_admin']) ? $role : 'staff',
            'is_active' => true,
        ]);

        $user->assignRole($role);

        if (!empty($permissions)) {
            $user->givePermissionTo($permissions);
        }

        return $user;
    }
}
