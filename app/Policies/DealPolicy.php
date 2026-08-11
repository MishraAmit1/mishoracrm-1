<?php

namespace App\Policies;

use App\Models\Deal;
use App\Models\User;

class DealPolicy
{
    public function view(User $user, Deal $deal): bool
    {
        if ($user->tenant_id !== $deal->tenant_id) {
            return false;
        }

        if ($user->user_type === 'superadmin' || $user->can('deals.view_all')) {
            return true;
        }

        return $user->can('deals.view_own') && $deal->assigned_to === $user->id;
    }

    public function modify(User $user, Deal $deal): bool
    {
        if ($user->tenant_id !== $deal->tenant_id) {
            return false;
        }

        if ($user->user_type === 'superadmin' || $user->can('deals.edit_all')) {
            return true;
        }

        return $user->can('deals.edit_own') && $deal->assigned_to === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Deal $deal): bool
    {
        return $this->modify($user, $deal);
    }

    public function delete(User $user, Deal $deal): bool
    {
        return $this->modify($user, $deal);
    }
}
