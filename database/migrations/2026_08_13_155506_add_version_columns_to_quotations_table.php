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
        Schema::table('quotations', function (Blueprint $table) {
            $table->foreignId('parent_quotation_id')
                  ->nullable()
                  ->after('deal_id')
                  ->constrained('quotations')
                  ->nullOnDelete();
            $table->unsignedInteger('version')->default(1)->after('parent_quotation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropForeign(['parent_quotation_id']);
            $table->dropColumn(['parent_quotation_id', 'version']);
        });
    }
};
