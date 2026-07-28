<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_pdf_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->string('primary_color', 7)->default('#1e3a5f');
            $table->string('accent_color', 7)->default('#3b82f6');
            $table->string('font_family')->default('DejaVu Sans');
            $table->string('logo_position')->default('left');
            $table->text('footer_note')->nullable();
            $table->boolean('show_bank_details')->default(true);
            $table->boolean('show_tax_summary')->default(true);
            $table->timestamps();

            $table->unique('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_pdf_settings');
    }
};
