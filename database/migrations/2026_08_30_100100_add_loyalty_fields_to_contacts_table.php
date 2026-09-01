<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Denormalised loyalty cache on the customer record, derived from the
     * loyalty_transactions ledger and maintained by LoyaltyService. Kept out
     * of Contact::$fillable — system-managed only.
     *
     *  - loyalty_points          : current spendable balance
     *  - loyalty_lifetime_points : gross points ever earned (drives the tier;
     *                              redeeming never lowers it)
     *  - loyalty_tier            : bronze | silver | gold (null until first earn)
     *  - loyalty_updated_at      : last points movement
     */
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->unsignedInteger('loyalty_points')->default(0)->after('notes');
            $table->unsignedInteger('loyalty_lifetime_points')->default(0)->after('loyalty_points');
            $table->string('loyalty_tier')->nullable()->after('loyalty_lifetime_points');
            $table->timestamp('loyalty_updated_at')->nullable()->after('loyalty_tier');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn([
                'loyalty_points',
                'loyalty_lifetime_points',
                'loyalty_tier',
                'loyalty_updated_at',
            ]);
        });
    }
};
