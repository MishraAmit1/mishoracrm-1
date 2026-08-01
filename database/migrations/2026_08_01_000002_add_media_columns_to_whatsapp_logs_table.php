<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Supports attaching an image/document to a WhatsApp send — media_id is
    // Meta's returned reference (kept for debugging/support), attachment_name
    // is the original filename shown in the Logs page.
    public function up(): void
    {
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            $table->string('media_type')->nullable()->after('is_bulk');
            $table->string('media_id')->nullable()->after('media_type');
            $table->string('attachment_name')->nullable()->after('media_id');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            $table->dropColumn(['media_type', 'media_id', 'attachment_name']);
        });
    }
};
