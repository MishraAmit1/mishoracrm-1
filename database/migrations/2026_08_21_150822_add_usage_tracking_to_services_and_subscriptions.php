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
        Schema::table('services', function (Blueprint $table) {
            // Total units included in the service (e.g. 10 sessions). Null =
            // not a quantity-limited service (e.g. a plain monthly membership).
            $table->unsignedInteger('total_quantity')->nullable()->after('duration_unit');
        });

        Schema::table('service_subscriptions', function (Blueprint $table) {
            $table->unsignedInteger('total_quantity')->nullable()->after('duration_unit'); // snapshot from Service
            $table->unsignedInteger('used_quantity')->default(0)->after('total_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('total_quantity');
        });

        Schema::table('service_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['total_quantity', 'used_quantity']);
        });
    }
};
