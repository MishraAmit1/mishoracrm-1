<?php

namespace App\Policies;

use App\Models\Followup;
use App\Models\User;

class FollowupPolicy
{
    public function view(User $user, Followup $followup): bool
    {
        if ($user->tenant_id !== $followup->tenant_id) {
            return false;
        }

        if ($user->user_type === 'superadmin' || $user->can('followups.view_all')) {
            return true;
        }

        return $user->can('followups.view_own') && $followup->assigned_to === $user->id;
    }

    public function modify(User $user, Followup $followup): bool
    {
        if ($user->tenant_id !== $followup->tenant_id) {
            return false;
        }

        if ($user->user_type === 'superadmin' || $user->can('followups.edit_all')) {
            return true;
        }

        return $user->can('followups.edit_own') && $followup->assigned_to === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('followups.create');
    }

    public function update(User $user, Followup $followup): bool
    {
        return $this->modify($user, $followup);
    }

    public function delete(User $user, Followup $followup): bool
    {
        if ($user->tenant_id !== $followup->tenant_id) {
            return false;
        }

        return $user->user_type === 'superadmin' || $user->can('followups.delete');
    }
}
