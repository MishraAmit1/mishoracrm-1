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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('company')->nullable();
            $table->string('source')->nullable();  // facebook, website, referral, whatsapp
            $table->string('designation')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->enum('status', ['new', 'contacted', 'qualified', 'lost', 'converted'])->default('new');
            $table->enum('priority', ['low', 'medium', 'high'])->nullable();
            $table->date('expected_close_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('lead_value', 10, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'assigned_to']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
