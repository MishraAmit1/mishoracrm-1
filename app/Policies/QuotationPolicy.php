<?php

namespace App\Policies;

use App\Models\Quotation;
use App\Models\User;

class QuotationPolicy
{
    public function view(User $user, Quotation $quotation): bool
    {
        if ($user->tenant_id !== $quotation->tenant_id) {
            return false;
        }

        if ($user->user_type === 'superadmin' || $user->can('quotations.view_all')) {
            return true;
        }

        return $user->can('quotations.view_own') && $quotation->created_by === $user->id;
    }

    // Quotations only have a single "quotations.edit" permission (no own/all
    // split), so modify access is: has the permission AND (created it OR has
    // view_all — a view_all user can still be blocked from editing others'
    // quotations by simply not granting edit, but if they can edit at all we
    // don't further restrict by ownership since there's no edit_own/edit_all pair).
    public function modify(User $user, Quotation $quotation): bool
    {
        if ($user->tenant_id !== $quotation->tenant_id) {
            return false;
        }

        if (!$user->can('quotations.edit')) {
            return false;
        }

        return $user->user_type === 'superadmin'
            || $user->can('quotations.view_all')
            || $quotation->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->user_type === 'superadmin' || $user->can('quotations.create');
    }

    public function update(User $user, Quotation $quotation): bool
    {
        return $this->modify($user, $quotation);
    }

    public function delete(User $user, Quotation $quotation): bool
    {
        if ($user->tenant_id !== $quotation->tenant_id) {
            return false;
        }

        if (!$user->can('quotations.delete')) {
            return false;
        }

        return $user->user_type === 'superadmin'
            || $user->can('quotations.view_all')
            || $quotation->created_by === $user->id;
    }
}
