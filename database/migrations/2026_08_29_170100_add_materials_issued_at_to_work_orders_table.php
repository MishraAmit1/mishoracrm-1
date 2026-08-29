<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * materials_issued_at — set when raw materials are physically issued to
     * the shop floor (at "Start Production"). Once set, the Work Order is
     * in WIP: materials are consumed, finished goods not yet produced.
     */
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->timestamp('materials_issued_at')->nullable()->after('started_at');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn('materials_issued_at');
        });
    }
};
