<?php

namespace App\Services;

use App\Models\GoodsReceiptNote;
use App\Models\Product;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;

class GoodsReceiptService
{
    /**
     * Record one receiving event (a GRN) against a Purchase Order.
     *
     * $lines is keyed by the PO item row index. Each entry:
     *   received_qty       — how much physically arrived in THIS delivery
     *   accepted_qty       — how much passed inspection (defaults to received)
     *   rejection_reason   — free text, only meaningful when some was rejected
     *   batch_number       — optional, for the accepted stock batch
     *   expiry_date        — optional
     *
     * Only the accepted quantity is credited to stock (weighted-average
     * costed from the PO line rate). Rejected quantity is recorded on the
     * GRN and accumulated on the PO line for the vendor follow-up.
     */
    public static function record(PurchaseOrder $po, array $lines, int $userId, ?string $receivedDate = null, ?string $note = null): GoodsReceiptNote
    {
        return DB::transaction(function () use ($po, $lines, $userId, $receivedDate, $note) {
            $poItems  = $po->items ?? [];
            $grnItems = [];

            foreach ($poItems as $index => $item) {
                $line = $lines[$index] ?? null;
                if (!$line) {
                    continue;
                }

                $ordered      = (float) ($item['quantity'] ?? 0);
                $prevAccepted = (float) ($item['received_quantity'] ?? 0);
                $prevRejected = (float) ($item['rejected_quantity'] ?? 0);

                $receivedNow = max(0.0, (float) ($line['received_qty'] ?? 0));
                if ($receivedNow <= 0) {
                    continue;
                }

                // Accepted defaults to the full received amount (QC optional).
                $acceptedNow = isset($line['accepted_qty']) && $line['accepted_qty'] !== ''
                    ? (float) $line['accepted_qty']
                    : $receivedNow;
                $acceptedNow = max(0.0, min($acceptedNow, $receivedNow));
                $rejectedNow = round($receivedNow - $acceptedNow, 2);

                // Never let cumulative accepted exceed what was ordered.
                $room        = max(0.0, $ordered - $prevAccepted);
                $acceptedNow = min($acceptedNow, $room);

                if ($acceptedNow > 0 && !empty($item['product_id'])) {
                    $product = Product::find($item['product_id']);
                    if ($product) {
                        StockService::receiveBatch(
                            $product,
                            $acceptedNow,
                            $line['batch_number'] ?? null,
                            $line['expiry_date'] ?? null,
                            $po->id,
                            null,
                            (float) ($item['rate'] ?? 0)
                        );
                    }
                }

                $poItems[$index]['received_quantity'] = round($prevAccepted + $acceptedNow, 2);
                $poItems[$index]['rejected_quantity'] = round($prevRejected + $rejectedNow, 2);

                $grnItems[] = [
                    'product_id'       => $item['product_id'] ?? null,
                    'name'             => $item['name'] ?? '',
                    'ordered_qty'      => $ordered,
                    'received_qty'     => round($receivedNow, 2),
                    'accepted_qty'     => round($acceptedNow, 2),
                    'rejected_qty'     => $rejectedNow,
                    'rejection_reason' => $rejectedNow > 0 ? ($line['rejection_reason'] ?? null) : null,
                    'batch_number'     => $line['batch_number'] ?? null,
                    'expiry_date'      => $line['expiry_date'] ?? null,
                ];
            }

            $po->update([
                'items'  => $poItems,
                'status' => static::deriveStatus($poItems),
            ]);

            return GoodsReceiptNote::create([
                'tenant_id'         => $po->tenant_id,
                'purchase_order_id' => $po->id,
                'vendor_id'         => $po->vendor_id,
                'number'            => GoodsReceiptNote::generateNumber($po->tenant_id),
                'received_date'     => $receivedDate ?: now()->toDateString(),
                'note'              => $note,
                'items'            => $grnItems,
                'created_by'        => $userId,
            ]);
        });
    }

    // received_quantity holds the cumulative ACCEPTED quantity.
    private static function deriveStatus(array $items): string
    {
        if (empty($items)) {
            return 'sent';
        }

        $anyAccepted = false;
        $allAccepted = true;

        foreach ($items as $item) {
            $ordered  = (float) ($item['quantity'] ?? 0);
            $accepted = (float) ($item['received_quantity'] ?? 0);

            if ($accepted > 0) {
                $anyAccepted = true;
            }
            if ($accepted < $ordered) {
                $allAccepted = false;
            }
        }

        if ($allAccepted) {
            return 'received';
        }

        return $anyAccepted ? 'partially_received' : 'sent';
    }
}
