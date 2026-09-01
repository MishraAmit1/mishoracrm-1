<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Optional side-effect a chatbot flow runs when it matches — currently
     * `loyalty_join` (enrol the sender in the loyalty programme + grant the
     * welcome bonus) alongside sending the flow's response message.
     */
    public function up(): void
    {
        Schema::table('whatsapp_chatbot_flows', function (Blueprint $table) {
            $table->string('action')->nullable()->after('response_message');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_chatbot_flows', function (Blueprint $table) {
            $table->dropColumn('action');
        });
    }
};
