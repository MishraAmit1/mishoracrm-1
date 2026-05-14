<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── WhatsApp Templates ────────────────────────────────────
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('body');           // supports {{name}}, {{company}} variables
            $table->string('category')->default('general'); // general, followup, reminder, promotion
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['tenant_id']);
        });

        // ── WhatsApp Logs ─────────────────────────────────────────
        Schema::create('whatsapp_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('template_id')->nullable()->constrained('whatsapp_templates')->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('to_phone');
            $table->string('to_name')->nullable();
            $table->text('message');
            $table->enum('status', ['sent', 'delivered', 'failed', 'pending'])->default('pending');
            $table->string('error_message')->nullable();
            $table->boolean('is_bulk')->default(false);
            $table->string('bulk_id')->nullable(); // group bulk sends
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'bulk_id']);
        });

        // ── Email Templates ───────────────────────────────────────
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('subject');
            $table->longText('body');       // HTML content, supports variables
            $table->string('category')->default('general');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['tenant_id']);
        });

        // ── Email Logs ────────────────────────────────────────────
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('template_id')->nullable()->constrained('email_templates')->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('to_email');
            $table->string('to_name')->nullable();
            $table->string('subject');
            $table->longText('body');
            $table->enum('status', ['sent', 'delivered', 'failed', 'pending'])->default('pending');
            $table->string('error_message')->nullable();
            $table->boolean('is_bulk')->default(false);
            $table->string('bulk_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'bulk_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('whatsapp_logs');
        Schema::dropIfExists('whatsapp_templates');
    }
};