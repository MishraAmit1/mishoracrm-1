<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            // Default credit period — a new Vendor Bill's due date is
            // seeded as bill date + this many days.
            $table->unsignedSmallInteger('payment_terms_days')->nullable()->after('gst_number');
            $table->text('bank_details')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['payment_terms_days', 'bank_details']);
        });
    }
};
