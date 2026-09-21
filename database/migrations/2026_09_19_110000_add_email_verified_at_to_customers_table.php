<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A customer-supplied email is just contact info until it's proven — only
     * a verified email may be used as a second Contact-linking key, otherwise
     * anyone could type someone else's email into their profile and pull that
     * person's shop cards into their own wallet.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('email_verified_at');
        });
    }
};
