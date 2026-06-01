<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->unique();
            $table->string('app_id')->nullable();
            $table->string('app_secret')->nullable();
            $table->text('access_token')->nullable();
            $table->string('instagram_account_id')->nullable();
            $table->string('page_id')->nullable();
            $table->string('webhook_verify_token')->nullable();
            $table->string('n8n_webhook_url')->nullable();
            $table->boolean('is_connected')->default(false);
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_settings');
    }
};
