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
        Schema::table('products', function (Blueprint $table) {
            $table->string('type')->default('finished_good')->after('product_code');
            $table->decimal('current_stock', 12, 2)->default(0)->after('unit');
            $table->decimal('reorder_level', 12, 2)->nullable()->after('current_stock');
            $table->decimal('reorder_quantity', 12, 2)->nullable()->after('reorder_level');
            $table->index(['tenant_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'type']);
            $table->dropColumn(['type', 'current_stock', 'reorder_level', 'reorder_quantity']);
        });
    }
};
