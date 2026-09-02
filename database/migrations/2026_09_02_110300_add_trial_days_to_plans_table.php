<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Per-plan free-trial length. A new signup on this plan gets this many days of
// full access before any payment is required.
//   0  = no free trial:
//          - a ₹0 plan → permanent free (no expiry, no lockout)
//          - a paid plan → the user is sent straight to checkout after signup
//   >0 = an N-day free trial, then the subscription expires until they pay
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedSmallInteger('trial_days')->default(0)->after('is_custom');
        });

        // Preserve today's behaviour: paid plans currently give a 14-day trial,
        // the ₹0 plan is permanent free.
        DB::table('plans')->where('monthly_price', '>', 0)->where('is_custom', false)
            ->update(['trial_days' => 14]);
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('trial_days');
        });
    }
};
