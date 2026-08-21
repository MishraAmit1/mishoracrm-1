<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->decimal('labor_cost', 12, 2)->default(0)->after('notes');
            $table->decimal('machine_cost', 12, 2)->default(0)->after('labor_cost');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn(['labor_cost', 'machine_cost']);
        });
    }
};
