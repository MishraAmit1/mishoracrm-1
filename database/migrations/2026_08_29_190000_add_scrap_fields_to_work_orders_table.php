<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Yield vs scrap at completion. `quantity` is the planned run size
     * (drives material consumption). `produced_quantity` is the good
     * output actually credited to finished-good stock; `scrap_quantity`
     * is what was rejected/wasted. The full run's material + labor +
     * machine cost is absorbed by the good units, so cost/unit rises with
     * scrap.
     */
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->decimal('produced_quantity', 12, 2)->nullable()->after('quantity');
            $table->decimal('scrap_quantity', 12, 2)->default(0)->after('produced_quantity');
            $table->string('scrap_reason')->nullable()->after('scrap_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn(['produced_quantity', 'scrap_quantity', 'scrap_reason']);
        });
    }
};
