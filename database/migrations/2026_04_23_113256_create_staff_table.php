<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Schema::create('departments', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
        //     $table->string('name');
        //     $table->text('description')->nullable();
        //     $table->timestamps();
        // });

        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employee_code')->nullable();
            $table->string('designation')->nullable();
            $table->decimal('salary', 12, 2)->nullable();
            $table->date('joining_date')->nullable();
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'intern'])->default('full_time');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id']);
            $table->unique(['tenant_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
        Schema::dropIfExists('departments');
    }
};