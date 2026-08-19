<?php

namespace App\Console\Commands;

use App\Models\ProductBatch;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendBatchExpiryAlerts extends Command
{
    protected $signature = 'products:check-batch-expiry {--days=7 : Alert window in days}';

    protected $description = 'Notify tenant admins about product batches expiring within the given window';

    public function handle(NotificationService $notifications): int
    {
        $days = (int) $this->option('days');

        $batches = ProductBatch::withoutGlobalScopes()
            ->expiringWithin($days)
            ->whereNull('expiry_notified_at')
            ->with('product')
            ->get();

        foreach ($batches as $batch) {
            if (!$batch->product) {
                continue;
            }

            $admins = User::withoutGlobalScopes()
                ->where('tenant_id', $batch->tenant_id)
                ->where('user_type', 'tenant_admin')
                ->where('is_active', true)
                ->get();

            foreach ($admins as $admin) {
                $notifications->send(
                    'product.batch_expiring',
                    $admin,
                    [
                        'name'          => $batch->product->name,
                        'batch_number'  => $batch->batch_number,
                        'quantity'      => $batch->quantity,
                        'expiry_date'   => $batch->expiry_date?->format('d M Y'),
                    ],
                    null,
                    route('tenant.products.batches', $batch->product_id),
                    $batch
                );
            }

            $batch->update(['expiry_notified_at' => now()]);
        }

        $this->info("Flagged {$batches->count()} expiring batch(es) (window: {$days}d).");

        return self::SUCCESS;
    }
}
