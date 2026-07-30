<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Per-channel delivery outcome: {"in_app":"sent","push":"failed",...}
            $table->json('channel_status')->nullable()->after('channels_sent');
            // Error message for channels that failed: {"push":"..."}
            $table->json('channel_errors')->nullable()->after('channel_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['channel_status', 'channel_errors']);
        });
    }
};
