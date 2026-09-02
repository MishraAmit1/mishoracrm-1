<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Customer Loyalty is now sellable as a plan feature (config/modules.php).
// Existing plans were seeded before this, so back-fill: any plan that already
// bundles the other top-tier premium modules should also include loyalty.
// Idempotent — re-running just re-sets the same key.
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('plans')->get() as $plan) {
            $features = json_decode($plan->features ?? '[]', true) ?: [];

            $isTopTier = ($features['manufacturing'] ?? false)
                || ($features['subscriptions'] ?? false)
                || ($features['appointments'] ?? false);

            if ($isTopTier && !($features['loyalty'] ?? false)) {
                $features['loyalty'] = true;
                DB::table('plans')->where('id', $plan->id)
                    ->update(['features' => json_encode($features)]);
            }
        }
    }

    public function down(): void
    {
        foreach (DB::table('plans')->get() as $plan) {
            $features = json_decode($plan->features ?? '[]', true) ?: [];

            if (array_key_exists('loyalty', $features)) {
                unset($features['loyalty']);
                DB::table('plans')->where('id', $plan->id)
                    ->update(['features' => json_encode($features)]);
            }
        }
    }
};
