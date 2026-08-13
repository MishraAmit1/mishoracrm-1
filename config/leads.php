<?php

return [

    // ── SLA ──────────────────────────────────────────────────────
    // Hours a 'new' lead can sit with no contact before it's flagged
    // as an SLA breach (see App\Console\Commands\CheckLeadSlaBreaches).
    'sla_hours' => env('LEAD_SLA_HOURS', 4),

    // ── Lead scoring ─────────────────────────────────────────────
    // Generic starter weights — tune these to your actual sales process.
    // See App\Services\LeadScoringService. Final score is capped at 100.
    'scoring' => [

        'source_weights' => [
            'referral'   => 25,
            'walk_in'    => 20,
            'website'    => 15,
            'whatsapp'   => 15,
            'google'     => 12,
            'linkedin'   => 12,
            'indiamart'  => 12,
            'justdial'   => 12,
            'tradeindia' => 10,
            'sulekha'    => 10,
            'facebook'   => 10,
            'instagram'  => 10,
            'email'      => 8,
            'cold_call'  => 5,
            'other'      => 5,
        ],

        'completeness_points' => [
            'has_email'   => 5,
            'has_company' => 5,
        ],

        // [minimum lead_value, points] — first match wins, ordered high to low.
        'value_bands' => [
            [100000, 25],
            [50000, 18],
            [10000, 10],
            [0, 5],
        ],

        'engagement_points_per_touch' => 4,
        'engagement_max_points'       => 20,

        // Recency bonus decays linearly to 0 over this many days since
        // last contact (or since creation, if never contacted).
        'recency_decay_days' => 14,
        'recency_max_points' => 15,
    ],

];
