<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\User;

// Load-based auto-assignment: a newly-unassigned lead goes to whichever
// active tenant user currently has the fewest open (non-converted,
// non-lost) leads. Self-balancing, no cursor/round-robin state to track.
class LeadAutoAssignmentService
{
    public static function assign(Lead $lead): ?User
    {
        $staff = User::where('tenant_id', $lead->tenant_id)
            ->where('is_active', true)
            ->withCount(['assignedLeads as active_leads_count' => function ($query) use ($lead) {
                $query->where('tenant_id', $lead->tenant_id)
                    ->whereNotIn('status', ['converted', 'lost']);
            }])
            ->orderBy('active_leads_count')
            ->orderBy('id')
            ->first();

        if (!$staff) {
            return null;
        }

        $lead->update(['assigned_to' => $staff->id]);

        return $staff;
    }
}
