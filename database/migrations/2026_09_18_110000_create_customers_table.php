<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Platform-level, non-tenant-scoped "Customer" actor — one global login
     * for a phone number, linked (via contacts.customer_id) to that same
     * person's Contact row in each shop they're a customer of. Deliberately
     * has no tenant_id / BelongsToTenant — see docs/customer-portal-loyalty.txt.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 15)->unique(); // normalized, last-10-digit
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('pin_hash')->nullable();
            $table->unsignedTinyInteger('pin_attempts')->default(0);
            $table->timestamp('pin_locked_until')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('blocked_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
