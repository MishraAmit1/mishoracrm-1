<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_templates', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('category'); // lead, whatsapp, invoice, crm, hr
            $table->string('suitable_for')->nullable(); // Real Estate, EdTech, E-commerce
            $table->text('features')->nullable(); // JSON array of bullet points
            $table->string('icon_type')->default('zap'); // icon name
            $table->string('color')->default('blue'); // card accent color
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_templates');
    }
};
