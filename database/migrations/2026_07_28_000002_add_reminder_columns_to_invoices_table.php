<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->timestamp('due_reminded_at')->nullable()->after('paid_at');
            $table->timestamp('overdue_reminded_at')->nullable()->after('due_reminded_at');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['due_reminded_at', 'overdue_reminded_at']);
        });
    }
};
