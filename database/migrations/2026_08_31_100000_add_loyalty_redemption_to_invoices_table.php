<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Loyalty points redeemed against an invoice. Points are treated as a
     * tender (post-tax rebate), not a price discount — so `subtotal`, the GST
     * breakdown and `total` are untouched; `loyalty_discount` only reduces the
     * cash `due_amount` (see Invoice::getDueAmountAttribute).
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedInteger('loyalty_points_redeemed')->default(0)->after('paid_amount');
            $table->decimal('loyalty_discount', 12, 2)->default(0)->after('loyalty_points_redeemed');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['loyalty_points_redeemed', 'loyalty_discount']);
        });
    }
};
