<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Loyalty engagement fields (Phase 3):
     *  - birthday / anniversary        : dates the auto-offer command matches on
     *  - birthday_greeted_on / …       : guard so a greeting fires at most once a year
     *  - referral_code                 : the customer's own code to share (unique per tenant)
     *  - referred_by_contact_id        : who referred this customer (self-FK)
     */
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->date('birthday')->nullable()->after('loyalty_updated_at');
            $table->date('anniversary')->nullable()->after('birthday');
            $table->date('birthday_greeted_on')->nullable()->after('anniversary');
            $table->date('anniversary_greeted_on')->nullable()->after('birthday_greeted_on');
            $table->string('referral_code')->nullable()->after('anniversary_greeted_on');
            $table->foreignId('referred_by_contact_id')->nullable()->after('referral_code')
                ->constrained('contacts')->nullOnDelete();

            $table->unique(['tenant_id', 'referral_code']);
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropForeign(['referred_by_contact_id']);
            $table->dropUnique(['tenant_id', 'referral_code']);
            $table->dropColumn([
                'birthday', 'anniversary', 'birthday_greeted_on', 'anniversary_greeted_on',
                'referral_code', 'referred_by_contact_id',
            ]);
        });
    }
};
