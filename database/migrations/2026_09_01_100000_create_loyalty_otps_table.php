<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One-time codes for the public "check my rewards" page. A visitor enters
     * their phone/email, we send a 6-digit code over WhatsApp or Email, and
     * they must enter it back before any points data is shown (identity
     * verification — see docs/customer-loyalty.txt §4).
     */
    public function up(): void
    {
        Schema::create('loyalty_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->string('identifier');            // phone or email as entered
            $table->string('channel');               // whatsapp | email
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('ip', 45)->nullable();
            // nullable so MySQL doesn't slap ON UPDATE CURRENT_TIMESTAMP on it
            // (the value is always set explicitly by LoyaltyOtp::issue()).
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'identifier']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_otps');
    }
};
