<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->enum('event_type', ['comment', 'dm_received', 'dm_sent', 'automation_triggered', 'chatbot_triggered', 'n8n_triggered']);
            $table->string('instagram_user_id')->nullable();
            $table->string('instagram_username')->nullable();
            $table->string('post_id')->nullable();
            $table->string('comment_id')->nullable();
            $table->string('message_id')->nullable();
            $table->text('incoming_text')->nullable();
            $table->text('outgoing_text')->nullable();
            $table->unsignedBigInteger('automation_id')->nullable();
            $table->unsignedBigInteger('chatbot_flow_id')->nullable();
            $table->enum('status', ['success', 'failed', 'skipped'])->default('success');
            $table->text('error_message')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'event_type']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_logs');
    }
};
