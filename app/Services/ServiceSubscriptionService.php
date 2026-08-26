<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\ServiceSubscription;

class ServiceSubscriptionService
{
    // ── Renew — extends expiry by the subscription's own snapshotted
    // duration (starting from today or the old expiry, whichever is later),
    // reactivates it, resets the reminder guard so the next cycle can
    // alert again, resets used_quantity for quantity-tracked services,
    // and auto-creates a draft renewal Invoice prefilled with the
    // subscribed service. Returns the created Invoice, or null if the
    // subscription has no linked Service to bill. Shared by the manual
    // "Renew"/"Bulk Renew" controller actions and the automatic
    // subscriptions:auto-renew command, so both stay in sync. ─────────
    public static function renew(ServiceSubscription $subscription, ?int $createdByUserId = null): ?Invoice
    {
        $service = $subscription->service;

        $base = $subscription->expires_at && $subscription->expires_at->gt(now())
            ? $subscription->expires_at
            : now();

        $newExpiry = ServiceSubscription::computeExpiry(
            $base,
            $subscription->duration_value,
            $subscription->duration_unit,
            $service?->billing_cycle
        );

        $invoice = null;

        if ($service) {
            $items = [[
                'service_id'  => $service->id,
                'description' => $service->name,
                'quantity'    => 1,
                'rate'        => (float) $service->rate,
                'tax_percent' => (float) $service->tax_percent,
            ]];

            $totals = Invoice::calculateTotals($items, 0, (float) $service->tax_percent);

            $invoice = Invoice::create(array_merge($totals, [
                'tenant_id'   => $subscription->tenant_id,
                'contact_id'  => $subscription->contact_id,
                'number'      => Invoice::generateNumber(),
                'date'        => now()->format('Y-m-d'),
                'due_date'    => now()->addDays(7)->format('Y-m-d'),
                'items'       => $items,
                'notes'       => "Renewal invoice for {$service->name}",
                'status'      => 'draft',
                'paid_amount' => 0,
                'created_by'  => $createdByUserId,
            ]));
        }

        $subscription->update([
            'status'             => 'active',
            'expires_at'         => $newExpiry ?? $subscription->expires_at,
            'expiry_notified_at' => null,
            'invoice_id'         => $invoice?->id ?? $subscription->invoice_id,
            'used_quantity'      => $subscription->hasQuantityTracking() ? 0 : $subscription->used_quantity,
        ]);

        return $invoice;
    }
}
