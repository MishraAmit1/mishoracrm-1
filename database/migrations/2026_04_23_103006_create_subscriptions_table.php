<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            // FK to plans added in 2026_04_23_103942_add_plan_foreign_key_to_subscriptions_table.php —
            // the plans table doesn't exist yet at this point in migration order.
            $table->foreignId('plan_id');
            $table->string('razorpay_subscription_id')->nullable();
            $table->enum('status', ['trial', 'active', 'cancelled', 'expired', 'past_due'])->default('trial');
            $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
