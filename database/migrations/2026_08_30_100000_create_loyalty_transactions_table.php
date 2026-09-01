<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Immutable ledger for the Customer Loyalty module (Phase 1). Every points
     * movement is one row; the contact's cached `loyalty_points` /
     * `loyalty_lifetime_points` are derived from these rows.
     *
     * `earn` rows are FIFO lots: `remaining_points` starts equal to `points`
     * and is drawn down by `redeem` / `expire` rows (oldest lot first) so that
     * time-based points expiry stays accurate. `redeem` / `expire` carry a
     * negative `points` and `remaining_points` = 0.
     */
    public function up(): void
    {
        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('type');                       // earn | redeem | expire | adjust
            $table->integer('points');                    // signed: + earn/positive adjust, - redeem/expire
            $table->integer('balance_after');             // contact balance immediately after this row
            $table->integer('remaining_points')->default(0); // unspent portion of an earn/positive-adjust lot
            $table->timestamp('earn_expires_at')->nullable(); // when this lot lapses (null = never)
            $table->string('description')->nullable();
            $table->string('source_type')->nullable();    // e.g. App\Models\Invoice
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'contact_id', 'type']);
            $table->index(['source_type', 'source_id']);
            $table->index(['type', 'earn_expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_transactions');
    }
};
