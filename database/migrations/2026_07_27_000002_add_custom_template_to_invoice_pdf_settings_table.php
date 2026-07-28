<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_pdf_settings', function (Blueprint $table) {
            $table->boolean('use_custom_template')->default(false)->after('logo_position');
            $table->longText('custom_html')->nullable()->after('use_custom_template');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_pdf_settings', function (Blueprint $table) {
            $table->dropColumn(['use_custom_template', 'custom_html']);
        });
    }
};
