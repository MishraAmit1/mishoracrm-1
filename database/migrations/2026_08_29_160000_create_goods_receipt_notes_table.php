<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A Goods Receipt Note (GRN) — one physical receiving event against a
     * Purchase Order. Records what arrived, and how much of it passed
     * quality inspection. Only the ACCEPTED quantity is credited to stock
     * (see GoodsReceiptService); rejected quantity is recorded here for the
     * vendor follow-up / debit-note conversation.
     */
    public function up(): void
    {
        Schema::create('goods_receipt_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number')->unique();
            $table->date('received_date');
            $table->text('note')->nullable();
            // [{ product_id, name, ordered_qty, received_qty, accepted_qty,
            //    rejected_qty, rejection_reason, batch_number, expiry_date }]
            $table->json('items');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'purchase_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_notes');
    }
};
