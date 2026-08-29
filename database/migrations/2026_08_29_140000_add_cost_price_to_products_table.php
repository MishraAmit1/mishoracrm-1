<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * cost_price = the item's current weighted-average acquisition cost
     * (auto-maintained by StockService when stock is received via a
     * Purchase Order or produced by a Work Order). Distinct from `rate`,
     * which is the selling price. Nullable — falls back to `rate` wherever
     * a cost basis is read, so pre-existing tenants keep working.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('cost_price', 12, 2)->nullable()->after('rate');
        });

        // Seed every existing product's cost from its current rate — the
        // best starting estimate we have (raw materials were almost always
        // entered at cost anyway). Weighted average takes over from the
        // next receipt onward.
        DB::table('products')->whereNull('cost_price')->update([
            'cost_price' => DB::raw('rate'),
        ]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('cost_price');
        });
    }
};
