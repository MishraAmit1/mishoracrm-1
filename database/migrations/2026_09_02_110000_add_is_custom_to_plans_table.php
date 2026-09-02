<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A "custom" plan (Enterprise / Contact Sales) has no fixed price and no
// self-serve checkout — the pricing page shows "Custom" and a "Talk to sales"
// button that opens the /contact-sales form instead.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->boolean('is_custom')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('is_custom');
        });
    }
};
