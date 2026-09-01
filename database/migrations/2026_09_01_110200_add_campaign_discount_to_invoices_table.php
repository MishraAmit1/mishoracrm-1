<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A loyalty campaign coupon applied to an invoice. Same "tender, not a
     * price cut" treatment as loyalty_discount — only the cash due drops.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('loyalty_campaign_recipient_id')->nullable()->after('loyalty_discount')
                ->constrained()->nullOnDelete();
            $table->decimal('campaign_discount', 12, 2)->default(0)->after('loyalty_campaign_recipient_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['loyalty_campaign_recipient_id']);
            $table->dropColumn(['loyalty_campaign_recipient_id', 'campaign_discount']);
        });
    }
};
