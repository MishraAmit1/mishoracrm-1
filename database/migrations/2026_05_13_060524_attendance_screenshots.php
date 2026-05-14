<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_screenshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('attendance_id')->constrained()->onDelete('cascade');
            $table->foreignId('staff_id')->constrained()->onDelete('cascade');
            $table->string('path');                    // storage path
            $table->string('filename');
            $table->bigInteger('file_size')->nullable(); // bytes
            $table->timestamp('captured_at');           // exact capture time
            $table->enum('type', ['auto', 'clockout'])  // auto=30min, clockout=final
                  ->default('auto');
            $table->timestamps();

            $table->index(['attendance_id', 'captured_at']);
            $table->index(['tenant_id', 'staff_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_screenshots');
    }
};