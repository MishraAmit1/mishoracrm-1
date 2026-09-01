<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The segment, frozen at launch time — one row per targeted customer, each
     * with the code they can redeem and its usage state.
     */
    public function up(): void
    {
        Schema::create('loyalty_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('loyalty_campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->timestamp('sent_at')->nullable();
            $table->unsignedInteger('redeemed_count')->default(0);
            $table->timestamp('redeemed_at')->nullable();
            $table->decimal('redeemed_value', 12, 2)->nullable();
            $table->timestamps();

            $table->unique(['loyalty_campaign_id', 'contact_id'], 'lcr_campaign_contact_unique');
            $table->index(['tenant_id', 'code'], 'lcr_tenant_code_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_campaign_recipients');
    }
};
