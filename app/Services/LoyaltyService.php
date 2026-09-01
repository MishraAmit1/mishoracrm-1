<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Invoice;
use App\Models\LoyaltyTransaction;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

/**
 * The Customer Loyalty engine (Phase 1). Single writer for the
 * loyalty_transactions ledger and the denormalised loyalty_* columns on
 * contacts. Every mutating method runs in a transaction with a row lock on
 * the contact so the running `balance_after` and the cache stay consistent.
 *
 * Tier is driven by lifetime (gross) points, so redeeming or expiring points
 * never demotes a customer.
 */
class LoyaltyService
{
    // ── Read helpers ─────────────────────────────────────────────

    // Points a bill of $amount would earn under the tenant's current rules,
    // including any flash / double-points multiplier active today.
    public function pointsForAmount(Tenant $tenant, float $amount): int
    {
        $s = $tenant->loyaltySettings();

        $block   = max(0.01, (float) $s['amount_per_point_block']);
        $perUnit = max(0, (int) $s['points_per_amount']);
        $base    = (int) floor(max(0, $amount) / $block) * $perUnit;

        return (int) round($base * $this->activeMultiplier($tenant));
    }

    // 1, unless today's weekday is one of settings['loyalty']['multiplier_days'].
    public function activeMultiplier(Tenant $tenant): float
    {
        $s    = $tenant->loyaltySettings();
        $days = array_map('strtolower', (array) ($s['multiplier_days'] ?? []));
        $mult = (float) ($s['multiplier'] ?? 1);

        if ($mult > 1 && in_array(strtolower(now()->format('D')), $days, true)) {
            return $mult;
        }

        return 1.0;
    }

    // Customer-facing summary — shared by the public rewards page, the API
    // lookup and the WhatsApp self-check.
    public function snapshot(Contact $contact): array
    {
        $tenant = $contact->tenant;
        $s      = $tenant?->loyaltySettings() ?? Tenant::LOYALTY_DEFAULTS;
        $block  = (int) $s['redeem_points_block'];

        $redeemableValue = $block > 0
            ? floor((int) $contact->loyalty_points / $block) * (float) $s['redeem_value']
            : 0.0;

        $recent = $contact->loyaltyTransactions()->limit(10)->get()->map(fn ($t) => [
            'type'        => $t->type,
            'points'      => (int) $t->points,
            'description' => $t->description,
            'date'        => $t->created_at->toDateString(),
        ])->all();

        return [
            'name'             => $contact->name,
            'points'           => (int) $contact->loyalty_points,
            'lifetime_points'  => (int) $contact->loyalty_lifetime_points,
            'tier'             => $contact->loyalty_tier,
            'tier_label'       => $contact->loyaltyTierLabel(),
            'redeemable_value' => round($redeemableValue, 2),
            'recent'           => $recent,
        ];
    }

    // ── Earn — invoice fully paid ────────────────────────────────

    // Award points for a fully-paid invoice. Idempotent: a second call for the
    // same invoice is a no-op. Returns the created earn row, or null when
    // nothing was awarded (module off, no contact, zero points, already done).
    public function awardForInvoice(Invoice $invoice): ?LoyaltyTransaction
    {
        $tenant = $invoice->tenant;

        if (!$tenant || !$tenant->hasModuleEnabled('loyalty') || !$invoice->contact_id) {
            return null;
        }

        if ($this->earnRowForInvoice($invoice)) {
            return null; // already awarded
        }

        // Earn on the cash the customer actually paid — points tendered via a
        // loyalty redemption don't earn fresh points.
        $cashPaid = max(0.0, (float) $invoice->total - (float) $invoice->loyalty_discount);
        $points   = $this->pointsForAmount($tenant, $cashPaid);

        if ($points <= 0) {
            return null;
        }

        $row = DB::transaction(function () use ($invoice, $tenant, $points) {
            $contact = Contact::withoutGlobalScopes()->lockForUpdate()->find($invoice->contact_id);
            if (!$contact) {
                return null;
            }

            $points = $this->applyDailyCap($contact, $tenant, $points);
            if ($points <= 0) {
                return null;
            }

            $s      = $tenant->loyaltySettings();
            $months = (int) $s['expiry_months'];

            return $this->credit($contact, LoyaltyTransaction::TYPE_EARN, $points, [
                'description'     => "Invoice {$invoice->number}",
                'source_type'     => Invoice::class,
                'source_id'       => $invoice->id,
                'earn_expires_at' => $months > 0 ? now()->addMonths($months) : null,
            ]);
        });

        if ($row) {
            $this->notifyEarn((int) $row->contact_id, (int) $row->points, 'your recent visit');
        }

        return $row;
    }

    // Undo the loyalty effect of an invoice that is no longer paid (status
    // moved away from paid, or the invoice was deleted). Claws back only the
    // portion of the original earn lot that is still unspent; already-redeemed
    // points are not pulled from other lots. Lifetime is reduced by the full
    // original earn so the tier reflects reality.
    public function reverseForInvoice(Invoice $invoice): void
    {
        $earn = $this->earnRowForInvoice($invoice);
        if (!$earn) {
            return;
        }

        DB::transaction(function () use ($earn, $invoice) {
            $contact = Contact::withoutGlobalScopes()->lockForUpdate()->find($earn->contact_id);
            if (!$contact) {
                return;
            }

            $lot = LoyaltyTransaction::withoutGlobalScopes()->lockForUpdate()->find($earn->id);

            $clawback = min((int) $lot->remaining_points, (int) $contact->loyalty_points);
            $lot->update(['remaining_points' => 0]);

            $newBalance  = max(0, (int) $contact->loyalty_points - $clawback);
            $newLifetime = max(0, (int) $contact->loyalty_lifetime_points - (int) $lot->points);

            if ($clawback > 0) {
                LoyaltyTransaction::create([
                    'tenant_id'        => $contact->tenant_id,
                    'contact_id'       => $contact->id,
                    'type'             => LoyaltyTransaction::TYPE_ADJUST,
                    'points'           => -$clawback,
                    'balance_after'    => $newBalance,
                    'remaining_points' => 0,
                    'description'      => "Reversal — Invoice {$invoice->number} no longer paid",
                    'source_type'      => Invoice::class,
                    'source_id'        => $invoice->id,
                ]);
            }

            $contact->forceFill([
                'loyalty_points'          => $newBalance,
                'loyalty_lifetime_points' => $newLifetime,
                'loyalty_updated_at'      => now(),
            ])->save();

            $this->recalculateTier($contact);
        });
    }

    // ── Redeem ──────────────────────────────────────────────────

    // Spend points. Throws \RuntimeException when the amount is not positive or
    // exceeds the balance. Does not touch lifetime → tier is unaffected.
    public function redeem(Contact $contact, int $points, string $description, $source = null, ?int $userId = null): LoyaltyTransaction
    {
        if ($points <= 0) {
            throw new \RuntimeException('Redeemed points must be positive.');
        }

        return DB::transaction(function () use ($contact, $points, $description, $source, $userId) {
            $contact = Contact::withoutGlobalScopes()->lockForUpdate()->find($contact->id);

            if ($points > (int) $contact->loyalty_points) {
                throw new \RuntimeException('Not enough points to redeem.');
            }

            $this->consumeLots($contact, $points);

            $newBalance = (int) $contact->loyalty_points - $points;

            $row = LoyaltyTransaction::create([
                'tenant_id'        => $contact->tenant_id,
                'contact_id'       => $contact->id,
                'type'             => LoyaltyTransaction::TYPE_REDEEM,
                'points'           => -$points,
                'balance_after'    => $newBalance,
                'remaining_points' => 0,
                'description'      => $description,
                'source_type'      => $source ? $source::class : null,
                'source_id'        => $source->id ?? null,
                'created_by'       => $userId,
            ]);

            $contact->forceFill([
                'loyalty_points'     => $newBalance,
                'loyalty_updated_at' => now(),
            ])->save();

            return $row;
        });
    }

    // ── Redemption against an invoice ───────────────────────────

    // How many points / how much value can be applied to this invoice right
    // now, respecting the customer's balance and the tenant's per-bill caps.
    // Returns ['points' => int, 'value' => float, 'error' => ?string].
    public function quoteRedemption(Invoice $invoice, ?int $requestedPoints = null): array
    {
        $tenant  = $invoice->tenant;
        $contact = $invoice->contact;
        $s       = $tenant?->loyaltySettings() ?? Tenant::LOYALTY_DEFAULTS;

        $block         = (int) $s['redeem_points_block'];
        $valuePerBlock = (float) $s['redeem_value'];

        if (!$contact || $block <= 0 || $valuePerBlock <= 0) {
            return ['points' => 0, 'value' => 0.0, 'error' => 'Redemption is not configured.'];
        }

        $balance  = (int) $contact->loyalty_points;
        $cashDue  = max(0.0, (float) $invoice->total - (float) $invoice->paid_amount - (float) $invoice->loyalty_discount);
        $policy   = (float) $s['max_discount_percent'] > 0
            ? round((float) $s['max_discount_percent'] / 100 * (float) $invoice->total, 2)
            : PHP_FLOAT_MAX;

        $maxValue = min($cashDue, $policy);

        $blocks = min(
            intdiv($balance, $block),
            (int) floor($maxValue / $valuePerBlock),
        );

        if ($requestedPoints !== null) {
            $blocks = min($blocks, intdiv(max(0, $requestedPoints), $block));
        }

        $points = $blocks * $block;
        $value  = round($blocks * $valuePerBlock, 2);

        if ($points <= 0) {
            return ['points' => 0, 'value' => 0.0, 'error' => 'No points can be applied to this bill.'];
        }

        if ((float) $s['min_discount'] > 0 && $value < (float) $s['min_discount']) {
            return ['points' => 0, 'value' => 0.0, 'error' => 'Minimum redemption is ' . number_format((float) $s['min_discount'], 2) . '.'];
        }

        return ['points' => $points, 'value' => $value, 'error' => null];
    }

    // Redeem points against the invoice: records the ledger entry and stamps
    // loyalty_points_redeemed / loyalty_discount on the invoice. Returns
    // ['ok' => bool, 'message' => string, 'points' => int, 'value' => float].
    public function applyRedemption(Invoice $invoice, ?int $requestedPoints, ?int $userId = null): array
    {
        $tenant = $invoice->tenant;

        if (!$tenant || !$tenant->hasModuleEnabled('loyalty') || !$invoice->contact_id) {
            return ['ok' => false, 'message' => 'Loyalty redemption is not available for this invoice.', 'points' => 0, 'value' => 0.0];
        }

        if ($invoice->status === 'paid') {
            return ['ok' => false, 'message' => 'This invoice is already settled.', 'points' => 0, 'value' => 0.0];
        }

        if ($invoice->hasLoyaltyRedemption()) {
            return ['ok' => false, 'message' => 'Points are already redeemed on this invoice. Remove them first.', 'points' => 0, 'value' => 0.0];
        }

        $quote = $this->quoteRedemption($invoice, $requestedPoints);
        if ($quote['error']) {
            return ['ok' => false, 'message' => $quote['error'], 'points' => 0, 'value' => 0.0];
        }

        DB::transaction(function () use ($invoice, $quote, $userId) {
            $this->redeem(
                $invoice->contact,
                $quote['points'],
                "Redeemed on Invoice {$invoice->number}",
                $invoice,
                $userId,
            );

            $invoice->forceFill([
                'loyalty_points_redeemed' => $quote['points'],
                'loyalty_discount'        => $quote['value'],
            ])->save();
        });

        return ['ok' => true, 'message' => "{$quote['points']} points redeemed.", 'points' => $quote['points'], 'value' => $quote['value']];
    }

    // Undo an invoice's loyalty redemption — credits the points back (as a
    // fresh lot, not counted toward lifetime again) and clears the invoice
    // columns. No-op if there is no redemption.
    public function reverseRedemption(Invoice $invoice, ?int $userId = null): void
    {
        $row = $this->redemptionRowForInvoice($invoice);
        if (!$row) {
            return;
        }

        $tenant = $invoice->tenant;
        $months = (int) ($tenant?->loyaltySettings()['expiry_months'] ?? 0);

        DB::transaction(function () use ($invoice, $row, $userId, $months) {
            $contact = Contact::withoutGlobalScopes()->lockForUpdate()->find($row->contact_id);
            if ($contact) {
                $this->credit($contact, LoyaltyTransaction::TYPE_ADJUST, (int) abs($row->points), [
                    'description'     => "Redemption reversed — Invoice {$invoice->number}",
                    'source_type'     => Invoice::class,
                    'source_id'       => $invoice->id,
                    'earn_expires_at' => $months > 0 ? now()->addMonths($months) : null,
                    'created_by'      => $userId,
                ], countsToLifetime: false);
            }

            $invoice->forceFill([
                'loyalty_points_redeemed' => 0,
                'loyalty_discount'        => 0,
            ])->save();
        });
    }

    // ── Standing reward catalog — "500 pts = 1 free coffee" ─────

    // Deducts the reward's points and notes the item on the invoice. Does NOT
    // change the cash due — staff add the free item to the order themselves.
    // ['ok' => bool, 'message' => string]
    public function redeemReward(Invoice $invoice, string $rewardName, ?int $userId = null): array
    {
        $fail = fn (string $m) => ['ok' => false, 'message' => $m];

        $tenant = $invoice->tenant;
        if (!$tenant || !$tenant->hasModuleEnabled('loyalty') || !$invoice->contact_id) {
            return $fail('Reward redemption is not available for this invoice.');
        }
        if ($invoice->status === 'paid') {
            return $fail('This invoice is already settled.');
        }
        if ($invoice->hasLoyaltyReward()) {
            return $fail('A reward is already redeemed on this invoice.');
        }

        $reward = collect($tenant->loyaltySettings()['reward_catalog'] ?? [])
            ->first(fn ($r) => strcasecmp(trim((string) ($r['name'] ?? '')), trim($rewardName)) === 0);

        $cost = (int) ($reward['points'] ?? 0);
        if (!$reward || $cost <= 0) {
            return $fail('That reward is not available.');
        }
        if ($cost > (int) $invoice->contact->loyalty_points) {
            return $fail('Not enough points for this reward.');
        }

        DB::transaction(function () use ($invoice, $reward, $cost, $userId) {
            $this->redeem($invoice->contact, $cost, "Reward: {$reward['name']} — Invoice {$invoice->number}", $invoice, $userId);
            $invoice->forceFill([
                'loyalty_reward'        => $reward['name'],
                'loyalty_reward_points' => $cost,
            ])->save();
        });

        return ['ok' => true, 'message' => "{$reward['name']} redeemed for {$cost} points — add the item to the order."];
    }

    public function reverseReward(Invoice $invoice, ?int $userId = null): void
    {
        $row = LoyaltyTransaction::withoutGlobalScopes()
            ->where('type', LoyaltyTransaction::TYPE_REDEEM)
            ->where('source_type', Invoice::class)
            ->where('source_id', $invoice->id)
            ->where('description', 'like', 'Reward:%')
            ->latest('id')
            ->first();

        $tenant = $invoice->tenant;
        $months = (int) ($tenant?->loyaltySettings()['expiry_months'] ?? 0);

        DB::transaction(function () use ($invoice, $row, $userId, $months) {
            if ($row) {
                $contact = Contact::withoutGlobalScopes()->lockForUpdate()->find($row->contact_id);
                if ($contact) {
                    $this->credit($contact, LoyaltyTransaction::TYPE_ADJUST, (int) abs($row->points), [
                        'description'     => "Reward reversed — Invoice {$invoice->number}",
                        'source_type'     => Invoice::class,
                        'source_id'       => $invoice->id,
                        'earn_expires_at' => $months > 0 ? now()->addMonths($months) : null,
                        'created_by'      => $userId,
                    ], countsToLifetime: false);
                }
            }

            $invoice->forceFill(['loyalty_reward' => null, 'loyalty_reward_points' => 0])->save();
        });
    }

    // ── Points expiry reminders ────────────────────────────────

    // One "your points expire soon" nudge per contact whose earn lots lapse
    // within the tenant's configured window. Returns contacts notified.
    public function sendExpiryReminders(Tenant $tenant): int
    {
        $days = (int) $tenant->loyaltySettings()['expiry_reminder_days'];
        if ($days <= 0 || !$tenant->hasModuleEnabled('loyalty')) {
            return 0;
        }

        $lots = LoyaltyTransaction::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('remaining_points', '>', 0)
            ->whereNull('expiry_reminded_at')
            ->whereNotNull('earn_expires_at')
            ->whereBetween('earn_expires_at', [now(), now()->addDays($days)])
            ->get();

        $sent = 0;
        foreach ($lots->groupBy('contact_id') as $contactId => $group) {
            $contact = Contact::withoutGlobalScopes()->find($contactId);
            if ($contact) {
                LoyaltyNotifier::expiryReminder(
                    $contact,
                    (int) $group->sum('remaining_points'),
                    $group->min('earn_expires_at'),
                );
                $sent++;
            }

            LoyaltyTransaction::withoutGlobalScopes()
                ->whereIn('id', $group->pluck('id'))
                ->update(['expiry_reminded_at' => now()]);
        }

        return $sent;
    }

    // ── Manual adjustment by the owner ──────────────────────────

    public function manualAdjust(Contact $contact, int $points, string $reason, ?int $userId = null): LoyaltyTransaction
    {
        if ($points === 0) {
            throw new \RuntimeException('Adjustment cannot be zero.');
        }

        $tenant = $contact->tenant;

        return DB::transaction(function () use ($contact, $points, $reason, $userId, $tenant) {
            $contact = Contact::withoutGlobalScopes()->lockForUpdate()->find($contact->id);

            if ($points > 0) {
                $s      = $tenant->loyaltySettings();
                $months = (int) $s['expiry_months'];

                return $this->credit($contact, LoyaltyTransaction::TYPE_ADJUST, $points, [
                    'description'     => $reason,
                    'earn_expires_at' => $months > 0 ? now()->addMonths($months) : null,
                    'created_by'      => $userId,
                ]);
            }

            // Negative — clamp so the balance can't go below zero.
            $debit = min(-$points, (int) $contact->loyalty_points);
            if ($debit <= 0) {
                throw new \RuntimeException('Balance is already zero.');
            }

            $this->consumeLots($contact, $debit);
            $newBalance = (int) $contact->loyalty_points - $debit;

            $row = LoyaltyTransaction::create([
                'tenant_id'        => $contact->tenant_id,
                'contact_id'       => $contact->id,
                'type'             => LoyaltyTransaction::TYPE_ADJUST,
                'points'           => -$debit,
                'balance_after'    => $newBalance,
                'remaining_points' => 0,
                'description'      => $reason,
                'created_by'       => $userId,
            ]);

            $contact->forceFill([
                'loyalty_points'     => $newBalance,
                'loyalty_updated_at' => now(),
            ])->save();

            return $row;
        });
    }

    // ── Engagement — referral & occasion bonuses (Phase 3) ─────

    // Gift both the referrer and the new customer the configured bonus.
    // Idempotent per (referrer, referee) pair. No-op if the module is off or
    // referral_bonus_points is 0.
    public function awardReferral(Contact $referrer, Contact $referee): void
    {
        $tenant = $referrer->tenant;
        if (!$tenant || !$tenant->hasModuleEnabled('loyalty')) {
            return;
        }

        $s     = $tenant->loyaltySettings();
        $bonus = (int) $s['referral_bonus_points'];
        if ($bonus <= 0) {
            return;
        }

        $alreadyDone = LoyaltyTransaction::withoutGlobalScopes()
            ->where('type', LoyaltyTransaction::TYPE_ADJUST)
            ->where('source_type', Contact::class)
            ->where('source_id', $referrer->id)
            ->where('contact_id', $referee->id)
            ->exists();
        if ($alreadyDone) {
            return;
        }

        $months = (int) $s['expiry_months'];
        $expiry = $months > 0 ? now()->addMonths($months) : null;

        $grants = [
            [$referee->id,  $referrer->id, "Referral bonus — referred by {$referrer->name}"],
            [$referrer->id, $referee->id,  "Referral bonus — {$referee->name} joined"],
        ];

        foreach ($grants as [$contactId, $otherId, $desc]) {
            DB::transaction(function () use ($contactId, $otherId, $desc, $bonus, $expiry) {
                $locked = Contact::withoutGlobalScopes()->lockForUpdate()->find($contactId);
                if ($locked) {
                    $this->credit($locked, LoyaltyTransaction::TYPE_ADJUST, $bonus, [
                        'description'     => $desc,
                        'source_type'     => Contact::class,
                        'source_id'       => $otherId,
                        'earn_expires_at' => $expiry,
                    ]);
                }
            });

            $this->notifyEarn($contactId, $bonus, 'referral bonus');
        }
    }

    // Gift the configured bonus for a birthday / anniversary, at most once per
    // calendar year. Returns the ledger row, or null when nothing was granted.
    public function grantOccasionBonus(Contact $contact, string $occasion): ?LoyaltyTransaction
    {
        if (!in_array($occasion, ['birthday', 'anniversary'], true)) {
            return null;
        }

        $tenant = $contact->tenant;
        if (!$tenant || !$tenant->hasModuleEnabled('loyalty')) {
            return null;
        }

        $bonus = (int) $tenant->loyaltySettings()["{$occasion}_bonus_points"];
        if ($bonus <= 0) {
            return null;
        }

        $greetedCol = "{$occasion}_greeted_on";
        if ($contact->$greetedCol && $contact->$greetedCol->year === now()->year) {
            return null; // already greeted this year
        }

        $months = (int) $tenant->loyaltySettings()['expiry_months'];
        $label  = $occasion === 'birthday' ? 'Birthday bonus' : 'Anniversary bonus';

        return DB::transaction(function () use ($contact, $bonus, $months, $label, $greetedCol) {
            $locked = Contact::withoutGlobalScopes()->lockForUpdate()->find($contact->id);

            $row = $this->credit($locked, LoyaltyTransaction::TYPE_ADJUST, $bonus, [
                'description'     => $label,
                'earn_expires_at' => $months > 0 ? now()->addMonths($months) : null,
            ]);

            $locked->forceFill([$greetedCol => now()->toDateString()])->save();

            return $row;
        });
    }

    // ── Expiry ──────────────────────────────────────────────────

    // Expire every earn lot past its earn_expires_at that still has points.
    // Balance drops; lifetime does not. Returns the number of lots expired.
    public function expireDuePoints(?int $tenantId = null): int
    {
        $lots = LoyaltyTransaction::withoutGlobalScopes()
            ->where('remaining_points', '>', 0)
            ->whereNotNull('earn_expires_at')
            ->where('earn_expires_at', '<=', now())
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->orderBy('created_at')
            ->get();

        $expired = 0;

        foreach ($lots as $lot) {
            DB::transaction(function () use ($lot, &$expired) {
                $contact = Contact::withoutGlobalScopes()->lockForUpdate()->find($lot->contact_id);
                $lot     = LoyaltyTransaction::withoutGlobalScopes()->lockForUpdate()->find($lot->id);

                if (!$contact || $lot->remaining_points <= 0) {
                    return;
                }

                $amount     = (int) $lot->remaining_points;
                $newBalance = max(0, (int) $contact->loyalty_points - $amount);

                $lot->update(['remaining_points' => 0]);

                LoyaltyTransaction::create([
                    'tenant_id'        => $contact->tenant_id,
                    'contact_id'       => $contact->id,
                    'type'             => LoyaltyTransaction::TYPE_EXPIRE,
                    'points'           => -$amount,
                    'balance_after'    => $newBalance,
                    'remaining_points' => 0,
                    'description'      => 'Points expired',
                    'source_type'      => LoyaltyTransaction::class,
                    'source_id'        => $lot->id,
                ]);

                $contact->forceFill([
                    'loyalty_points'     => $newBalance,
                    'loyalty_updated_at' => now(),
                ])->save();

                $expired++;
            });
        }

        return $expired;
    }

    // ── Tier ────────────────────────────────────────────────────

    public function recalculateTier(Contact $contact): void
    {
        $lifetime = (int) $contact->loyalty_lifetime_points;

        $tier = null;
        if ($lifetime > 0) {
            $tiers = $contact->tenant->loyaltySettings()['tiers'] ?? [];
            asort($tiers);
            foreach ($tiers as $name => $threshold) {
                if ($lifetime >= (int) $threshold) {
                    $tier = $name;
                }
            }
        }

        if ($tier !== $contact->loyalty_tier) {
            $contact->forceFill(['loyalty_tier' => $tier])->save();
        }
    }

    // ── Internals ───────────────────────────────────────────────

    // Create a positive-points row (earn or positive adjust) as a new FIFO lot
    // and roll the contact cache forward. Assumes it is already inside a
    // transaction with the contact locked.
    private function credit(Contact $contact, string $type, int $points, array $extra, bool $countsToLifetime = true): LoyaltyTransaction
    {
        $newBalance  = (int) $contact->loyalty_points + $points;
        $newLifetime = (int) $contact->loyalty_lifetime_points + ($countsToLifetime ? $points : 0);

        $row = LoyaltyTransaction::create(array_merge([
            'tenant_id'        => $contact->tenant_id,
            'contact_id'       => $contact->id,
            'type'             => $type,
            'points'           => $points,
            'balance_after'    => $newBalance,
            'remaining_points' => $points,
        ], $extra));

        $contact->forceFill([
            'loyalty_points'          => $newBalance,
            'loyalty_lifetime_points' => $newLifetime,
            'loyalty_updated_at'      => now(),
        ])->save();

        if ($countsToLifetime) {
            $this->recalculateTier($contact);
        }

        return $row;
    }

    // Draw $points down from the oldest open earn lots.
    private function consumeLots(Contact $contact, int $points): void
    {
        $remaining = $points;

        $lots = LoyaltyTransaction::withoutGlobalScopes()
            ->where('contact_id', $contact->id)
            ->openLots()
            ->lockForUpdate()
            ->get();

        foreach ($lots as $lot) {
            if ($remaining <= 0) {
                break;
            }
            $take = min((int) $lot->remaining_points, $remaining);
            $lot->update(['remaining_points' => (int) $lot->remaining_points - $take]);
            $remaining -= $take;
        }
    }

    private function applyDailyCap(Contact $contact, Tenant $tenant, int $points): int
    {
        $cap = (int) $tenant->loyaltySettings()['max_points_per_day'];
        if ($cap <= 0) {
            return $points;
        }

        $earnedToday = (int) LoyaltyTransaction::withoutGlobalScopes()
            ->where('contact_id', $contact->id)
            ->where('type', LoyaltyTransaction::TYPE_EARN)
            ->whereDate('created_at', now()->toDateString())
            ->sum('points');

        return max(0, min($points, $cap - $earnedToday));
    }

    // Fire the opt-in "you earned points" nudge, with the customer's freshly
    // updated balance. Safe to call outside a transaction.
    private function notifyEarn(int $contactId, int $points, string $reason): void
    {
        $contact = Contact::withoutGlobalScopes()->find($contactId);
        if ($contact) {
            LoyaltyNotifier::pointsAwarded($contact, $points, $reason);
        }
    }

    private function earnRowForInvoice(Invoice $invoice): ?LoyaltyTransaction
    {
        return LoyaltyTransaction::withoutGlobalScopes()
            ->where('type', LoyaltyTransaction::TYPE_EARN)
            ->where('source_type', Invoice::class)
            ->where('source_id', $invoice->id)
            ->first();
    }

    private function redemptionRowForInvoice(Invoice $invoice): ?LoyaltyTransaction
    {
        return LoyaltyTransaction::withoutGlobalScopes()
            ->where('type', LoyaltyTransaction::TYPE_REDEEM)
            ->where('source_type', Invoice::class)
            ->where('source_id', $invoice->id)
            ->where(fn ($q) => $q->whereNull('description')->orWhere('description', 'not like', 'Reward:%'))
            ->latest('id')
            ->first();
    }
}
