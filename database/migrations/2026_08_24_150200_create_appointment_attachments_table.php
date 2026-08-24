<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('appointment_id')->constrained()->onDelete('cascade');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->enum('stage', ['before', 'after'])->nullable();
            $table->timestamps();

            $table->index(['appointment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_attachments');
    }
};
