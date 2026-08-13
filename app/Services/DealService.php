<?php

namespace App\Services;

use App\Models\Deal;

// Shared create/update/stage-transition logic for deals, used by both the
// Web and API DealControllers so probability defaults, actual_close_date,
// and stage_changed_at tracking stay identical across both. Side effects
// (webhooks, notifications, quotation auto-accept) are handled separately
// via DealObserver + its listeners once these methods call Deal::update()/
// Deal::create() — no need to trigger them here.
class DealService
{
    public static function defaultProbability(string $stage): int
    {
        return match ($stage) {
            'new'         => 10,
            'proposal'    => 30,
            'negotiation' => 60,
            'won'         => 100,
            'lost'        => 0,
            default       => 10,
        };
    }

    // ── Create a new deal ───────────────────────────────────────────
    public static function create(array $data, int $tenantId, int $createdBy): Deal
    {
        $data['tenant_id']  = $tenantId;
        $data['created_by'] = $createdBy;

        if (empty($data['probability'])) {
            $data['probability'] = self::defaultProbability($data['stage']);
        }

        if ($data['stage'] === 'won' && empty($data['actual_close_date'])) {
            $data['actual_close_date'] = now()->toDateString();
        }

        $data['stage_changed_at'] = now();

        return Deal::create($data);
    }

    // ── Update an existing deal from a form/API payload ─────────────
    public static function update(Deal $deal, array $data): Deal
    {
        $justWon = $data['stage'] === 'won' && $deal->stage !== 'won';

        if ($justWon) {
            $data['actual_close_date'] = now()->toDateString();
        }

        if (empty($data['probability'])) {
            $data['probability'] = self::defaultProbability($data['stage']);
        }

        if (isset($data['stage']) && $data['stage'] !== $deal->stage) {
            $data['stage_changed_at'] = now();
        }

        $deal->update($data);

        return $deal;
    }

    // ── Move a deal to a new stage (Kanban drag / quick action) ─────
    public static function updateStage(Deal $deal, string $stage, ?string $lostReason = null): Deal
    {
        $data = [
            'stage'            => $stage,
            'probability'      => self::defaultProbability($stage),
            'stage_changed_at' => now(),
        ];

        if ($stage === 'won') {
            $data['actual_close_date'] = now()->toDateString();
        }

        if ($stage === 'lost' && $lostReason) {
            $data['lost_reason'] = $lostReason;
        }

        $deal->update($data);

        return $deal;
    }

    public static function markWon(Deal $deal): Deal
    {
        $deal->update([
            'stage'             => 'won',
            'probability'       => 100,
            'actual_close_date' => now()->toDateString(),
            'stage_changed_at'  => now(),
        ]);

        return $deal;
    }

    public static function markLost(Deal $deal, ?string $reason): Deal
    {
        $deal->update([
            'stage'             => 'lost',
            'probability'       => 0,
            'actual_close_date' => now()->toDateString(),
            'lost_reason'       => $reason,
            'stage_changed_at'  => now(),
        ]);

        return $deal;
    }
}
