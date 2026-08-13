<?php

namespace App\Services;

use App\Models\Lead;

// Generic starter weighted-score model — see config/leads.php for the
// tunable weights. Recalculated on creation and on engagement (call/note
// logged); not a per-request calculation, so it can be sorted/filtered on.
class LeadScoringService
{
    public static function recalculate(Lead $lead): int
    {
        $cfg = config('leads.scoring');

        $score = $cfg['source_weights'][$lead->source] ?? 0;

        if ($lead->email)   $score += $cfg['completeness_points']['has_email'] ?? 0;
        if ($lead->company) $score += $cfg['completeness_points']['has_company'] ?? 0;

        $value = (float) ($lead->lead_value ?? 0);
        foreach ($cfg['value_bands'] as [$min, $points]) {
            if ($value >= $min) {
                $score += $points;
                break;
            }
        }

        $engagementCount = $lead->callLogs()->count() + $lead->followups()->count();
        $score += min(
            $engagementCount * ($cfg['engagement_points_per_touch'] ?? 0),
            $cfg['engagement_max_points'] ?? 0
        );

        $lastActivity      = $lead->contacted_at ?? $lead->created_at;
        $daysSinceActivity = $lastActivity->diffInDays(now());
        $decayDays         = max($cfg['recency_decay_days'] ?? 1, 1);
        $recencyFactor     = max(0, 1 - ($daysSinceActivity / $decayDays));
        $score += (int) round($recencyFactor * ($cfg['recency_max_points'] ?? 0));

        $score = max(0, min(100, $score));

        $lead->update(['score' => $score]);

        return $score;
    }
}
