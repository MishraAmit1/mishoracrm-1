<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->decimal('gst_percentage', 5, 2)->default(0)->after('discount_amount');
            $table->decimal('gst_amount', 10, 2)->default(0)->after('gst_percentage');
            $table->decimal('total_amount', 10, 2)->default(0)->after('gst_amount');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['gst_percentage', 'gst_amount', 'total_amount']);
        });
    }
};
