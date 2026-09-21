<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One-time login codes for the global customer portal. Same shape as
     * loyalty_otps minus tenant_id — a Customer isn't tenant-scoped.
     */
    public function up(): void
    {
        Schema::create('customer_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone', 15);             // normalized last-10 digits
            $table->string('channel');               // whatsapp | email
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('ip', 45)->nullable();
            // nullable so MySQL doesn't slap ON UPDATE CURRENT_TIMESTAMP on it
            // (the value is always set explicitly by CustomerOtp::issue()).
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['phone', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_otps');
    }
};
