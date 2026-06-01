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
        Schema::table('leads', function (Blueprint $table) {
            $table->string('lost_reason', 500)->nullable()->after('notes');
            $table->datetime('contacted_at')->nullable()->after('lost_reason');
            $table->datetime('qualified_at')->nullable()->after('contacted_at');
            $table->datetime('converted_at')->nullable()->after('qualified_at');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('lost_reason');
            $table->dropColumn('contacted_at');
            $table->dropColumn('qualified_at');
            $table->dropColumn('converted_at');
        });
    }
};
