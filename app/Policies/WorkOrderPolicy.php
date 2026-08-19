<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkOrder;

class WorkOrderPolicy
{
    public function view(User $user, WorkOrder $workOrder): bool
    {
        if ($user->tenant_id !== $workOrder->tenant_id) {
            return false;
        }

        if ($user->user_type === 'superadmin' || $user->can('work_orders.view_all')) {
            return true;
        }

        return $user->can('work_orders.view_own') && $workOrder->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->user_type === 'superadmin' || $user->can('work_orders.create');
    }

    // Details (product/quantity/notes) are only editable before production
    // has started — once in_progress the BOM has been committed to.
    public function modify(User $user, WorkOrder $workOrder): bool
    {
        if ($user->tenant_id !== $workOrder->tenant_id) {
            return false;
        }

        if (!$workOrder->isEditable()) {
            return false;
        }

        if (!$user->can('work_orders.edit')) {
            return false;
        }

        return $user->user_type === 'superadmin'
            || $user->can('work_orders.view_all')
            || $workOrder->created_by === $user->id;
    }

    public function update(User $user, WorkOrder $workOrder): bool
    {
        return $this->modify($user, $workOrder);
    }

    // Start/Complete/Cancel — the production state-transition actions.
    // Gated on a distinct permission from "edit" since a shop-floor role
    // may progress work orders without being allowed to change what/how
    // much is being built.
    public function manage(User $user, WorkOrder $workOrder): bool
    {
        if ($user->tenant_id !== $workOrder->tenant_id) {
            return false;
        }

        if (in_array($workOrder->status, ['completed', 'cancelled'], true)) {
            return false;
        }

        return $user->user_type === 'superadmin' || $user->can('work_orders.manage');
    }

    // Deletion is blocked once production has started (in_progress) or
    // finished (completed) — stock has already moved by that point and
    // deleting the record would erase that trail. Cancelled/pending
    // work orders never touched stock, so they're safe to delete.
    public function delete(User $user, WorkOrder $workOrder): bool
    {
        if ($user->tenant_id !== $workOrder->tenant_id) {
            return false;
        }

        if (in_array($workOrder->status, ['in_progress', 'completed'], true)) {
            return false;
        }

        if (!$user->can('work_orders.delete')) {
            return false;
        }

        return $user->user_type === 'superadmin'
            || $user->can('work_orders.view_all')
            || $workOrder->created_by === $user->id;
    }
}
