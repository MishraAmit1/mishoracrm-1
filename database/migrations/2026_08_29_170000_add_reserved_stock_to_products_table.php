<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * reserved_stock = quantity of this product earmarked for open Work
     * Orders that have not yet issued their materials. Available-to-promise
     * is current_stock - reserved_stock. The physical current_stock only
     * drops when materials are actually issued to the shop floor.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('reserved_stock', 12, 2)->default(0)->after('current_stock');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('reserved_stock');
        });
    }
};
