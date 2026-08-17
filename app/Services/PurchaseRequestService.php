<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;

class PurchaseRequestService
{
    // ── Create — collision-safe number generation ──────────────────
    // number is a unique column generated from max(id)+1; two concurrent
    // submissions can race and compute the same number, so retry a few
    // times on a unique-constraint violation rather than 500ing.
    public static function store(array $data, int $tenantId, int $userId): PurchaseRequest
    {
        return retry(3, function () use ($data, $tenantId, $userId) {
            return PurchaseRequest::create(array_merge($data, [
                'tenant_id'    => $tenantId,
                'number'       => PurchaseRequest::generateNumber(),
                'requested_by' => $userId,
                'status'       => 'pending',
            ]));
        }, 50, fn ($e) => static::isNumberCollision($e));
    }

    // ── Update — only reachable while pending, enforced by policy ──
    public static function update(PurchaseRequest $purchaseRequest, array $data): PurchaseRequest
    {
        $purchaseRequest->update($data);

        return $purchaseRequest;
    }

    private static function isNumberCollision(\Throwable $e): bool
    {
        return $e instanceof \Illuminate\Database\QueryException
            && str_contains(strtolower($e->getMessage()), 'number');
    }

    // ── Approve — flip to approved→converted, auto-create draft PO ──
    public static function approve(PurchaseRequest $purchaseRequest, int $approvedByUserId): ?PurchaseOrder
    {
        if ($purchaseRequest->purchaseOrder) {
            return null;
        }

        $purchaseRequest->update([
            'status'      => 'approved',
            'approved_by' => $approvedByUserId,
            'approved_at' => now(),
        ]);

        $items = collect($purchaseRequest->items ?? [])->map(fn ($item) => [
            'product_id'        => $item['product_id'] ?? null,
            'name'               => $item['name'] ?? '',
            'description'        => $item['description'] ?? null,
            'quantity'           => $item['quantity'] ?? 0,
            'rate'               => 0,
            'tax_percent'        => 0,
            'amount'             => 0,
            'received_quantity'  => 0,
        ])->toArray();

        $purchaseOrder = retry(3, function () use ($purchaseRequest, $items, $approvedByUserId) {
            return PurchaseOrder::create([
                'tenant_id'            => $purchaseRequest->tenant_id,
                'vendor_id'            => null,
                'purchase_request_id'  => $purchaseRequest->id,
                'number'               => PurchaseOrder::generateNumber(),
                'date'                 => now()->toDateString(),
                'items'                => $items,
                'subtotal'             => 0,
                'discount'             => 0,
                'tax_percent'          => 0,
                'tax_amount'           => 0,
                'total'                => 0,
                'status'               => 'draft',
                'created_by'           => $approvedByUserId,
            ]);
        }, 50, fn ($e) => static::isNumberCollision($e));

        $purchaseRequest->update(['status' => 'converted']);

        return $purchaseOrder;
    }

    // ── Reject — flip to rejected, store reason ─────────────────────
    public static function reject(PurchaseRequest $purchaseRequest, ?string $reason): PurchaseRequest
    {
        $purchaseRequest->update([
            'status'            => 'rejected',
            'rejection_reason'  => $reason,
        ]);

        return $purchaseRequest;
    }
}
