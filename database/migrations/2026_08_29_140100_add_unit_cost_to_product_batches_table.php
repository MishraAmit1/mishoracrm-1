<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * unit_cost = the actual per-unit acquisition cost of this specific
     * batch (PO line rate on receipt, or computed production cost on a
     * Work Order finished-good batch). Kept alongside the aggregate
     * weighted-average on products.cost_price for batch-level traceability
     * and any future FIFO/actual-cost valuation.
     */
    public function up(): void
    {
        Schema::table('product_batches', function (Blueprint $table) {
            $table->decimal('unit_cost', 12, 2)->nullable()->after('initial_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('product_batches', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });
    }
};
