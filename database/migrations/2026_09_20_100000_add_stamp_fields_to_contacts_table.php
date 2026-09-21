<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stamp-card ("buy 5, get the 6th free") state, alongside the points
     * cache. System-managed by LoyaltyService only — not in Contact::$fillable.
     *
     *  - stamp_count          : progress on the current card
     *  - stamps_lifetime      : gross stamps ever earned
     *  - stamp_rewards_earned : completed cards whose reward is still unclaimed
     *  - stamp_updated_at     : last stamp movement (drives partial-card expiry)
     */
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->unsignedInteger('stamp_count')->default(0)->after('loyalty_updated_at');
            $table->unsignedInteger('stamps_lifetime')->default(0)->after('stamp_count');
            $table->unsignedInteger('stamp_rewards_earned')->default(0)->after('stamps_lifetime');
            $table->timestamp('stamp_updated_at')->nullable()->after('stamp_rewards_earned');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['stamp_count', 'stamps_lifetime', 'stamp_rewards_earned', 'stamp_updated_at']);
        });
    }
};
