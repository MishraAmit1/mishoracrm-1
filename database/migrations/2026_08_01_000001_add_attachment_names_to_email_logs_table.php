<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Stores just the original filenames (comma-joined) for display in the
    // Email Logs page — the files themselves are not persisted, only sent.
    public function up(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->string('attachment_names')->nullable()->after('is_bulk');
        });
    }

    public function down(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropColumn('attachment_names');
        });
    }
};
