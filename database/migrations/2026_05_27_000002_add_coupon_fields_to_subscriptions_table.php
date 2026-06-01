<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('coupon_id')->nullable()->after('plan_id');
            $table->decimal('original_amount', 10, 2)->nullable()->after('coupon_id');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('original_amount');

            $table->foreign('coupon_id')->references('id')->on('coupons')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['coupon_id']);
            $table->dropColumn(['coupon_id', 'original_amount', 'discount_amount']);
        });
    }
};
