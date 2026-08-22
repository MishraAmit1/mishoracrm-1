<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            // Frozen at completion time so later BOM/rate changes don't
            // retroactively alter a finished Work Order's recorded cost.
            // Null while pending/in_progress — the model falls back to a
            // live BOM calculation for those (preview-only) states.
            $table->decimal('material_cost_snapshot', 12, 2)->nullable()->after('machine_cost');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn('material_cost_snapshot');
        });
    }
};
