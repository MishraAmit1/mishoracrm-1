<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('exception_class', 200)->nullable()->index();
            $table->integer('http_status')->nullable()->index();
            $table->text('message');
            $table->text('url')->nullable();
            $table->string('method', 10)->nullable();
            $table->text('stack_trace')->nullable();
            $table->json('request_data')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->boolean('is_resolved')->default(false)->index();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_logs');
    }
};
