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
            $table->string('service_code')->nullable()->after('name');
            $table->string('hsn')->nullable()->after('tax_percent');
            $table->string('billing_cycle')->default('one_time')->after('unit');
            $table->unsignedInteger('duration_value')->nullable()->after('billing_cycle');
            $table->string('duration_unit')->nullable()->after('duration_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['service_code', 'hsn', 'billing_cycle', 'duration_value', 'duration_unit']);
        });
    }
};
