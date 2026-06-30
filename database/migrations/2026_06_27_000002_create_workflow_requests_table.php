<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('workflow_template_id')->nullable()->constrained()->onDelete('set null');
            $table->string('business_type');
            $table->text('problem_description');
            $table->string('contact_preference')->default('whatsapp'); // whatsapp, email
            $table->string('contact_value'); // phone number or email
            $table->enum('status', ['new', 'in_progress', 'completed', 'rejected'])->default('new');
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_requests');
    }
};
