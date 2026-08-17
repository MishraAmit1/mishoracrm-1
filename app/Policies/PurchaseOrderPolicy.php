<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy
{
    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        if ($user->tenant_id !== $purchaseOrder->tenant_id) {
            return false;
        }

        if ($user->user_type === 'superadmin' || $user->can('purchase_orders.view_all')) {
            return true;
        }

        return $user->can('purchase_orders.view_own') && $purchaseOrder->created_by === $user->id;
    }

    // Single "purchase_orders.edit" permission (no own/all split) — has
    // the permission AND (created it OR has view_all), mirroring Quotation.
    public function modify(User $user, PurchaseOrder $purchaseOrder): bool
    {
        if ($user->tenant_id !== $purchaseOrder->tenant_id) {
            return false;
        }

        if (!$user->can('purchase_orders.edit')) {
            return false;
        }

        return $user->user_type === 'superadmin'
            || $user->can('purchase_orders.view_all')
            || $purchaseOrder->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->user_type === 'superadmin' || $user->can('purchase_orders.create');
    }

    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $this->modify($user, $purchaseOrder);
    }

    public function delete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        if ($user->tenant_id !== $purchaseOrder->tenant_id) {
            return false;
        }

        if (!$user->can('purchase_orders.delete')) {
            return false;
        }

        return $user->user_type === 'superadmin'
            || $user->can('purchase_orders.view_all')
            || $purchaseOrder->created_by === $user->id;
    }

    // Receive is only reachable once the PO has actually been sent to the
    // vendor and isn't fully received yet.
    public function receive(User $user, PurchaseOrder $purchaseOrder): bool
    {
        if ($user->tenant_id !== $purchaseOrder->tenant_id) {
            return false;
        }

        if (!in_array($purchaseOrder->status, ['sent', 'partially_received'])) {
            return false;
        }

        return $user->user_type === 'superadmin' || $user->can('purchase_orders.receive');
    }
}
