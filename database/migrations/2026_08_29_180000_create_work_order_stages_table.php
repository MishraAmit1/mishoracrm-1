<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Production routing — the ordered steps a Work Order passes through
     * (e.g. Cutting → Welding → QC → Packing). A Work Order with no stage
     * rows behaves exactly as before (single start → complete). When it
     * has stages, completion is blocked until every stage is done/skipped.
     */
    public function up(): void
    {
        Schema::create('work_order_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->string('name');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['pending', 'in_progress', 'done', 'skipped'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'work_order_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_stages');
    }
};
