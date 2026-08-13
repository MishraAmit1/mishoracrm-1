<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->string('currency', 3)->default('INR')->after('total');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('currency', 3)->default('INR')->after('total');
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn('currency');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }
};
