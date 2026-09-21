<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Links a per-tenant Contact to the platform-level Customer that owns
     * its phone number. phone_verified doubles as the "Is this you?"
     * pending-confirmation signal (false = linked but unconfirmed for a
     * history-having contact; true = thin-contact auto-link or customer
     * confirmed). link_flagged_at drives the tenant "Needs review" list
     * when a customer says "Not me". See docs/customer-portal-loyalty.txt §2.
     */
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('phone_normalized')
                ->constrained('customers')->nullOnDelete();
            $table->boolean('phone_verified')->default(false)->after('customer_id');
            $table->timestamp('link_flagged_at')->nullable()->after('phone_verified');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->index(['tenant_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropIndex(['tenant_id', 'customer_id']);
            $table->dropColumn(['customer_id', 'phone_verified', 'link_flagged_at']);
        });
    }
};
