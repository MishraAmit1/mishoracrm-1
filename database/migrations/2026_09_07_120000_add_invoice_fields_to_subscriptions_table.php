<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Platform-billing tax invoice, issued once a subscription payment succeeds.
// The subscription row itself carries the invoice — one paid subscription
// term = one invoice — so no separate table is needed.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('invoice_number')->nullable()->unique()->after('razorpay_signature');
            $table->timestamp('invoice_issued_at')->nullable()->after('invoice_number');
            // { email: {status, to, at}, whatsapp: {status, to, at} } — last delivery attempt.
            $table->json('invoice_delivery')->nullable()->after('invoice_issued_at');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['invoice_number', 'invoice_issued_at', 'invoice_delivery']);
        });
    }
};
