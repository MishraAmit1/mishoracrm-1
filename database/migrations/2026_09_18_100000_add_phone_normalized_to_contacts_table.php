<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indexed, normalized (last-10-digit) copy of `phone`, kept in sync by
     * Contact's `saving` observer. Every phone lookup in the app (WhatsApp
     * matching, public rewards OTP, API loyalty lookup, customer-portal
     * linking) should query this column instead of the raw `phone` column,
     * which previously required an unindexed RIGHT(REPLACE(...)) scan.
     */
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('phone_normalized', 15)->nullable()->after('phone');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->index(['tenant_id', 'phone_normalized']);
            $table->index('phone_normalized');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'phone_normalized']);
            $table->dropIndex(['phone_normalized']);
            $table->dropColumn('phone_normalized');
        });
    }
};
