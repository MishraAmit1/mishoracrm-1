<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_chatbot_flows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->json('trigger_keywords'); // ["hi","hello","start"]
            $table->enum('keyword_match', ['any', 'exact', 'contains'])->default('contains');
            // Response can be simple text or multi-step JSON
            $table->text('response_message');
            $table->json('quick_replies')->nullable(); // [{text:"Learn More", payload:"learn_more"}]
            $table->boolean('is_default')->default(false); // fallback if no match
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
        Schema::dropIfExists('instagram_chatbot_flows');
    }
};
