<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // When the platform-subscription "expiring soon" reminder was last
            // sent for this row. Kept null on a fresh row so the reminder fires
            // once per subscription term (a renewal creates a new row).
            $table->timestamp('renewal_reminder_sent_at')->nullable()->after('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('renewal_reminder_sent_at');
        });
    }
};
