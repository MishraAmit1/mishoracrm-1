<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Owner-driven targeted offer (see docs/customer-loyalty.txt §5). Pick a
     * customer segment, a reward, and how the code is issued, then launch —
     * recipients are snapshotted into loyalty_campaign_recipients at that point.
     */
    public function up(): void
    {
        Schema::create('loyalty_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');

            // Segment — who gets it
            $table->string('segment_type');          // spend | visits | inactive | tier | manual
            $table->json('segment_config')->nullable();

            // Reward — what they get
            $table->string('reward_type');           // percent | flat | points | free_item
            $table->decimal('reward_value', 12, 2)->default(0);
            $table->string('reward_item')->nullable();
            $table->decimal('max_discount', 12, 2)->nullable();   // cap for percent

            // Code + guardrails
            $table->string('code_mode')->default('unique');       // unique | shared
            $table->string('shared_code')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('usage_limit_per_customer')->default(1);
            $table->unsignedInteger('total_redemption_cap')->nullable();
            $table->unsignedInteger('redeemed_count')->default(0);

            // Delivery + lifecycle
            $table->string('delivery')->default('none');          // none | whatsapp | email | both
            $table->string('status')->default('draft');           // draft | active | ended
            $table->timestamp('launched_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_campaigns');
    }
};
