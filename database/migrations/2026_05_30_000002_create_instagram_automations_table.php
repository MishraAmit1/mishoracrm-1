<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_automations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            // Trigger: comment on any post OR specific post
            $table->enum('trigger_type', ['any_post_comment', 'specific_post_comment', 'dm_keyword']);
            $table->string('post_id')->nullable();
            $table->json('trigger_keywords')->nullable(); // ["buy","price","info"]
            $table->enum('keyword_match', ['any', 'exact', 'contains'])->default('contains');
            // Action
            $table->enum('action_type', ['send_dm', 'reply_comment', 'trigger_n8n']);
            $table->text('dm_message')->nullable();
            $table->text('comment_reply')->nullable();
            $table->string('n8n_webhook_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('triggered_count')->default(0);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_automations');
    }
};
