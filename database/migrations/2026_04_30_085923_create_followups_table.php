<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('followups', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                  ->constrained()
                  ->onDelete('cascade');

            $table->foreignId('lead_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();

            $table->foreignId('contact_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();

            $table->foreignId('assigned_to')
                  ->constrained('users')
                  ->onDelete('cascade');

            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->enum('type', [
                'call',
                'email',
                'whatsapp',
                'meeting',
                'other',
            ])->default('call');

            $table->timestamp('scheduled_at');
            $table->timestamp('done_at')->nullable();

            $table->text('notes')->nullable();
            $table->text('outcome')->nullable();   // call ke baad kya hua

            $table->enum('status', [
                'scheduled',
                'done',
                'missed',
                'rescheduled',
            ])->default('scheduled');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'scheduled_at']);
            $table->index(['tenant_id', 'lead_id']);
            $table->index(['tenant_id', 'assigned_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('followups');
    }
};