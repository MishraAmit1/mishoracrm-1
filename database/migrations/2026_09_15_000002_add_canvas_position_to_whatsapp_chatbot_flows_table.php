<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_chatbot_flows', function (Blueprint $table) {
            $table->integer('canvas_x')->nullable()->after('sort_order');
            $table->integer('canvas_y')->nullable()->after('canvas_x');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_chatbot_flows', function (Blueprint $table) {
            $table->dropColumn(['canvas_x', 'canvas_y']);
        });
    }
};
