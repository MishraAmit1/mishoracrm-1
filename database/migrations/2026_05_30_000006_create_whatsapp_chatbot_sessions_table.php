<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_chatbot_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('wa_id'); // WhatsApp phone number
            $table->string('contact_name')->nullable();
            $table->unsignedBigInteger('chatbot_flow_id')->nullable();
            $table->json('context')->nullable(); // conversation state
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'wa_id']);
        });

        // Add whatsapp_settings columns to existing setup — store per-tenant WA Business API creds
        Schema::create('whatsapp_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->unique();
            $table->string('phone_number_id')->nullable();
            $table->string('waba_id')->nullable();
            $table->text('access_token')->nullable();
            $table->string('webhook_verify_token')->nullable();
            $table->string('n8n_webhook_url')->nullable();
            $table->boolean('chatbot_enabled')->default(false);
            $table->boolean('is_connected')->default(false);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_chatbot_sessions');
        Schema::dropIfExists('whatsapp_settings');
    }
};
