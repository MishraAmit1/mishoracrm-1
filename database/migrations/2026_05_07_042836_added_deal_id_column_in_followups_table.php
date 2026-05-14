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
        Schema::table('followups', function (Blueprint $table) {
                $table->foreignId('deal_id')
                  ->nullable()
                  ->constrained('deals')
                  ->nullOnDelete()->after('contact_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('followups', function (Blueprint $table) {
            $table->dropForeign(['deal_id']);
            $table->dropColumn('deal_id');
        });
    }
};
