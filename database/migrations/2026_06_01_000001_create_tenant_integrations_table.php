<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_integrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('platform');           // meta_lead_ads | indiamart | justdial | tradeindia | sulekha
            $table->boolean('is_active')->default(false);
            $table->text('credentials')->nullable(); // encrypted JSON: API keys, tokens
            $table->json('settings')->nullable();    // non-sensitive config: field mappings, form IDs
            $table->string('webhook_token', 64)->unique()->nullable(); // unique per tenant+platform
            $table->timestamp('last_synced_at')->nullable();
            $table->unsignedInteger('leads_imported')->default(0);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unique(['tenant_id', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_integrations');
    }
};
