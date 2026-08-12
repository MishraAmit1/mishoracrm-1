<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->enum('recurrence_type', ['none', 'daily', 'weekly', 'monthly'])->default('none')->after('tags');
            $table->unsignedInteger('recurrence_interval')->default(1)->after('recurrence_type');
            $table->date('recurrence_end_date')->nullable()->after('recurrence_interval');
            $table->foreignId('recurrence_parent_id')->nullable()->after('recurrence_end_date')
                ->constrained('tasks')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recurrence_parent_id');
            $table->dropColumn(['recurrence_type', 'recurrence_interval', 'recurrence_end_date']);
        });
    }
};
