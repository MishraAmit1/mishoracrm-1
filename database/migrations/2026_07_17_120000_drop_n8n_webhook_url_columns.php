<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Instagram and WhatsApp automation now run entirely in the Laravel
    // backend (keyword matching + Graph/Cloud API calls) — the optional
    // n8n webhook forwarding has been removed, so these columns are dead.
    private array $tables = [
        'whatsapp_settings',
        'whatsapp_chatbot_flows',
        'whatsapp_chatbot_sessions',
        'instagram_settings',
        'instagram_automations',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasColumn($table, 'n8n_webhook_url')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('n8n_webhook_url');
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (!Schema::hasColumn($table, 'n8n_webhook_url')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->string('n8n_webhook_url')->nullable();
                });
            }
        }
    }
};
