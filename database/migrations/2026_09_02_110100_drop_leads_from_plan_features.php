<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// The per-plan "leads" quota was never enforced anywhere in the app (only
// features['users'] is checked, in StaffController). It was pure marketing copy
// and has been removed from every pricing surface. Strip the now-dead key from
// existing plan rows so the stored JSON matches what the UI shows. Idempotent.
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('plans')->get() as $plan) {
            $features = json_decode($plan->features ?? '[]', true) ?: [];

            if (array_key_exists('leads', $features)) {
                unset($features['leads']);
                DB::table('plans')->where('id', $plan->id)
                    ->update(['features' => json_encode($features)]);
            }
        }
    }

    // Irreversible — the old quota values are not recoverable and were never
    // used. No-op so the migration can still be rolled back cleanly.
    public function down(): void
    {
    }
};
