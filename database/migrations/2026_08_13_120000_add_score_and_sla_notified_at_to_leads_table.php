<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->unsignedTinyInteger('score')->nullable()->default(0)->after('lead_value');
            $table->timestamp('sla_notified_at')->nullable()->after('qualified_at');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['score', 'sla_notified_at']);
        });
    }
};
