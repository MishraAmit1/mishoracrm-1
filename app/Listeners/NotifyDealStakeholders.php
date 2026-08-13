<?php

namespace App\Listeners;

use App\Events\DealAssigned;
use App\Events\DealCreated;
use App\Events\DealLost;
use App\Events\DealStageChanged;
use App\Events\DealWon;
use App\Services\NotificationService;

// In-app notifications for deal lifecycle events, wiring the deal.* types
// defined in config/notifications.php. Pattern mirrors NotifyLeadStakeholders.
class NotifyDealStakeholders
{
    public function handleDealCreated(DealCreated $event): void
    {
        $deal = $event->deal;

        if (!$deal->assigned_to || auth()->id() === $deal->assigned_to) {
            return;
        }

        NotificationService::notify(
            'deal.created',
            $deal->assignedTo,
            ['title' => $deal->title, 'value' => $deal->value],
            auth()->user(),
            route('tenant.deals.show', $deal->id),
            $deal
        );
    }

    public function handleDealStageChanged(DealStageChanged $event): void
    {
        $deal = $event->deal;

        // won/lost transitions get their own richer notification below.
        if (in_array($event->newStage, ['won', 'lost'])) {
            return;
        }

        if (!$deal->assigned_to || auth()->id() === $deal->assigned_to) {
            return;
        }

        NotificationService::notify(
            'deal.stage_changed',
            $deal->assignedTo,
            ['title' => $deal->title, 'stage' => $event->newStage],
            auth()->user(),
            route('tenant.deals.show', $deal->id),
            $deal
        );
    }

    public function handleDealWon(DealWon $event): void
    {
        $deal = $event->deal;

        if (!$deal->assigned_to) {
            return;
        }

        NotificationService::notify(
            'deal.won',
            $deal->assignedTo,
            ['title' => $deal->title, 'value' => $deal->value],
            auth()->user(),
            route('tenant.deals.show', $deal->id),
            $deal
        );
    }

    public function handleDealLost(DealLost $event): void
    {
        $deal = $event->deal;

        if (!$deal->assigned_to) {
            return;
        }

        NotificationService::notify(
            'deal.lost',
            $deal->assignedTo,
            ['title' => $deal->title],
            auth()->user(),
            route('tenant.deals.show', $deal->id),
            $deal
        );
    }

    public function handleDealAssigned(DealAssigned $event): void
    {
        $deal = $event->deal;

        if (!$deal->assigned_to || $event->assignedBy?->id === $deal->assigned_to) {
            return;
        }

        NotificationService::notify(
            'deal.assigned',
            $deal->assignedTo,
            ['title' => $deal->title],
            $event->assignedBy,
            route('tenant.deals.show', $deal->id),
            $deal
        );
    }
}
