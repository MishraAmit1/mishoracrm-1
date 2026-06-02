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
        Schema::table('custom_field_values', function (Blueprint $table) {
            $table->unsignedBigInteger('custom_field_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('custom_field_values', function (Blueprint $table) {
            $table->unsignedBigInteger('custom_field_id')->nullable(false)->change();
        });
    }
};
