<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Custom field definitions — tenant ke by
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

            // Kis module pe — lead, contact, deal, quotation, task
            $table->string('module');

            // Field ki details
            $table->string('label');          // "Budget Range"
            $table->string('field_key');      // "budget_range" (auto-generated, unique per tenant+module)
            $table->enum('field_type', [
                'text',
                'number',
                'date',
                'datetime',
                'dropdown',
                'multi_select',
                'checkbox',
                'textarea',
                'url',
                'email',
                'phone',
            ])->default('text');

            $table->json('options')->nullable();    // For dropdown / multi_select
            $table->string('placeholder')->nullable();
            $table->string('default_value')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('show_in_list')->default(false);   // Table mein column show ho?
            $table->boolean('show_in_filter')->default(false); // Filter mein show ho?
            $table->integer('sort_order')->default(0);

            $table->timestamps();

            $table->index(['tenant_id', 'module']);
            $table->unique(['tenant_id', 'module', 'field_key']);
        });

        // Custom field values — actual data
        Schema::create('custom_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('custom_field_id')->constrained()->onDelete('cascade');

            // Polymorphic — Lead, Contact, Deal, etc.
            $table->string('model_type');      // App\Models\Lead
            $table->unsignedBigInteger('model_id');

            $table->text('value')->nullable();  // JSON for multi_select, plain text for others

            $table->timestamps();

            $table->index(['model_type', 'model_id']);
            $table->index(['tenant_id', 'custom_field_id']);
            $table->unique(['custom_field_id', 'model_type', 'model_id'], 'unique_field_value');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_values');
        Schema::dropIfExists('custom_fields');
    }
};