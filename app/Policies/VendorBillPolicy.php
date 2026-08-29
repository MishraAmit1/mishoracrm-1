<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VendorBill;

// Mirrors PurchaseOrderPolicy — single edit/delete permission gated by
// "created it OR has view_all".
class VendorBillPolicy
{
    public function view(User $user, VendorBill $bill): bool
    {
        if ($user->tenant_id !== $bill->tenant_id) {
            return false;
        }

        if ($user->user_type === 'superadmin' || $user->can('vendor_bills.view_all')) {
            return true;
        }

        return $user->can('vendor_bills.view_own') && $bill->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->user_type === 'superadmin' || $user->can('vendor_bills.create');
    }

    public function modify(User $user, VendorBill $bill): bool
    {
        if ($user->tenant_id !== $bill->tenant_id) {
            return false;
        }

        if (!$user->can('vendor_bills.edit')) {
            return false;
        }

        return $user->user_type === 'superadmin'
            || $user->can('vendor_bills.view_all')
            || $bill->created_by === $user->id;
    }

    public function update(User $user, VendorBill $bill): bool
    {
        return $this->modify($user, $bill);
    }

    public function delete(User $user, VendorBill $bill): bool
    {
        if ($user->tenant_id !== $bill->tenant_id) {
            return false;
        }

        if (!$user->can('vendor_bills.delete')) {
            return false;
        }

        return $user->user_type === 'superadmin'
            || $user->can('vendor_bills.view_all')
            || $bill->created_by === $user->id;
    }

    public function recordPayment(User $user, VendorBill $bill): bool
    {
        if ($user->tenant_id !== $bill->tenant_id) {
            return false;
        }

        if (in_array($bill->status, ['paid', 'cancelled'], true)) {
            return false;
        }

        return $user->user_type === 'superadmin' || $user->can('vendor_bills.record_payment');
    }
}
