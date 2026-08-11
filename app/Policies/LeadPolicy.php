<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    public function view(User $user, Lead $lead): bool
    {
        if ($user->tenant_id !== $lead->tenant_id) {
            return false;
        }

        if ($user->user_type === 'superadmin' || $user->can('leads.view_all')) {
            return true;
        }

        return $user->can('leads.view_own') && $lead->assigned_to === $user->id;
    }

    public function modify(User $user, Lead $lead): bool
    {
        if ($user->tenant_id !== $lead->tenant_id) {
            return false;
        }

        if ($user->user_type === 'superadmin' || $user->can('leads.edit_all')) {
            return true;
        }

        return $user->can('leads.edit_own') && $lead->assigned_to === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Lead $lead): bool
    {
        return $this->modify($user, $lead);
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $this->modify($user, $lead);
    }
}
