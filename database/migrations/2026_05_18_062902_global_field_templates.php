<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SuperAdmin global templates
        Schema::create('global_field_templates', function (Blueprint $table) {
            $table->id();
            $table->string('module');
            $table->string('label');
            $table->string('field_key');
            $table->string('field_type')->default('text');
            $table->json('options')->nullable();
            $table->string('placeholder')->nullable();
            $table->string('description')->nullable();
            $table->boolean('is_system')->default(false);       // Core field — always visible
            $table->boolean('is_recommended')->default(false);  // Suggested to tenants
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['module', 'field_key']);
            $table->index('module');
        });

        // Tenant ke specific field assignments
        // (Tenant admin decide karta hai kaunse fields active hon)
        Schema::create('tenant_field_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('module');

            // Either global template OR custom field
            $table->foreignId('global_template_id')->nullable()
                  ->constrained('global_field_templates')->nullOnDelete();
            $table->foreignId('custom_field_id')->nullable()
                  ->constrained('custom_fields')->nullOnDelete();

            $table->boolean('is_active')->default(true);
            $table->boolean('is_required')->default(false); // Override
            $table->boolean('show_in_list')->default(false);
            $table->boolean('show_in_filter')->default(false);
            $table->integer('sort_order')->default(0);

            $table->timestamps();

            $table->index(['tenant_id', 'module']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_field_assignments');
        Schema::dropIfExists('global_field_templates');
    }
};