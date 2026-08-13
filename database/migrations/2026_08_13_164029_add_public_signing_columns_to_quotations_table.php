<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->string('public_token', 64)->nullable()->unique()->after('version');
            $table->string('signed_name')->nullable()->after('public_token');
            $table->longText('signature_data')->nullable()->after('signed_name');
            $table->string('customer_response_ip')->nullable()->after('signature_data');
            $table->timestamp('customer_responded_at')->nullable()->after('customer_response_ip');
            $table->string('rejected_reason', 500)->nullable()->after('customer_responded_at');
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn([
                'public_token',
                'signed_name',
                'signature_data',
                'customer_response_ip',
                'customer_responded_at',
                'rejected_reason',
            ]);
        });
    }
};
