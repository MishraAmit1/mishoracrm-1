<?php

namespace App\Jobs;

use App\Models\TenantIntegration;
use App\Services\Integrations\IndiaMartLeadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Runs on schedule (every 30 min) to pull new leads from IndiaMART for all active tenants.
 *
 * Schedule in app/Console/Kernel.php (if using old-style) OR bootstrap/app.php:
 *
 *   Schedule::job(new SyncIndiaMartLeadsJob)->everyThirtyMinutes();
 *
 * Or dispatch per-tenant if needed:
 *   SyncIndiaMartLeadsJob::dispatch($integration);
 */
class SyncIndiaMartLeadsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(
        private ?TenantIntegration $integration = null
    ) {}

    public function handle(): void
    {
        if ($this->integration) {
            $this->syncOne($this->integration);
            return;
        }

        // Sync all active IndiaMART integrations
        TenantIntegration::where('platform', 'indiamart')
            ->where('is_active', true)
            ->each(fn($integration) => $this->syncOne($integration));
    }

    private function syncOne(TenantIntegration $integration): void
    {
        try {
            $service  = IndiaMartLeadService::forIntegration($integration);
            $imported = $service->sync();

            if ($imported > 0) {
                Log::info("IndiaMART sync: {$imported} leads imported for tenant {$integration->tenant_id}");
            }
        } catch (\Throwable $e) {
            Log::error("IndiaMART sync failed for tenant {$integration->tenant_id}: {$e->getMessage()}");
        }
    }
}
