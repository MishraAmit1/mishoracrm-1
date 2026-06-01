<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    public function view(User $user, Lead $lead): bool
    {
        return $user->tenant_id === $lead->tenant_id;
    }

    public function modify(User $user, Lead $lead): bool
    {
        if ($user->tenant_id !== $lead->tenant_id) {
            return false;
        }

        return $user->user_type === 'tenant_admin' || $lead->assigned_to === $user->id;
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
