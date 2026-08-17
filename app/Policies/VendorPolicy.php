<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vendor;

// Vendors are shared master data within a tenant (no own/all split) — a
// single permission per action gates all tenant users the same way.
class VendorPolicy
{
    public function view(User $user, Vendor $vendor): bool
    {
        if ($user->tenant_id !== $vendor->tenant_id) {
            return false;
        }

        return $user->user_type === 'superadmin' || $user->can('vendors.view');
    }

    public function create(User $user): bool
    {
        return $user->user_type === 'superadmin' || $user->can('vendors.create');
    }

    public function modify(User $user, Vendor $vendor): bool
    {
        if ($user->tenant_id !== $vendor->tenant_id) {
            return false;
        }

        return $user->user_type === 'superadmin' || $user->can('vendors.edit');
    }

    public function update(User $user, Vendor $vendor): bool
    {
        return $this->modify($user, $vendor);
    }

    public function delete(User $user, Vendor $vendor): bool
    {
        if ($user->tenant_id !== $vendor->tenant_id) {
            return false;
        }

        return $user->user_type === 'superadmin' || $user->can('vendors.delete');
    }
}
