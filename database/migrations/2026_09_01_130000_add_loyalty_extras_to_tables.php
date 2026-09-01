<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Supporting columns for the Loyalty §7 extras:
     *  - loyalty_transactions.expiry_reminded_at — guard so the "your points
     *    expire soon" nudge fires once per earn lot.
     *  - invoices.loyalty_reward / loyalty_reward_points — a standing
     *    catalog reward ("500 pts = 1 free coffee") redeemed on a bill;
     *    separate from the points-for-discount fields.
     */
    public function up(): void
    {
        Schema::table('loyalty_transactions', function (Blueprint $table) {
            $table->timestamp('expiry_reminded_at')->nullable()->after('earn_expires_at');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('loyalty_reward')->nullable()->after('campaign_discount');
            $table->unsignedInteger('loyalty_reward_points')->default(0)->after('loyalty_reward');
        });
    }

    public function down(): void
    {
        Schema::table('loyalty_transactions', function (Blueprint $table) {
            $table->dropColumn('expiry_reminded_at');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['loyalty_reward', 'loyalty_reward_points']);
        });
    }
};
