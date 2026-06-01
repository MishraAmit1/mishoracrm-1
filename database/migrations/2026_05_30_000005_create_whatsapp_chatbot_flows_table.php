<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_chatbot_flows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->json('trigger_keywords'); // ["hi","hello","price"]
            $table->enum('keyword_match', ['any', 'exact', 'contains'])->default('contains');
            $table->text('response_message');
            $table->json('quick_replies')->nullable(); // button-style replies for WA
            $table->string('n8n_webhook_url')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('triggered_count')->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_chatbot_flows');
    }
};
