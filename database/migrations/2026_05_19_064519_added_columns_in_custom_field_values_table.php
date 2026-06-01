<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_field_values', function (Blueprint $table) {

            // field_key — stable unique identifier (e.g. 'budget_range')
            // Upsert key ke taur pe use hota hai
            $table->string('field_key')->nullable()->after('model_id');

            // assignment_id — TenantFieldAssignment ka id
            // Edit form mein prefill ke liye
            $table->unsignedBigInteger('assignment_id')->nullable()->after('field_key');

            // Index for fast lookup
            $table->index(['tenant_id', 'model_type', 'model_id', 'field_key'], 'cfv_lookup_idx');
            $table->index('assignment_id');
        });
    }

    public function down(): void
    {
        Schema::table('custom_field_values', function (Blueprint $table) {
            $table->dropIndex('cfv_lookup_idx');
            $table->dropIndex(['assignment_id']);
            $table->dropColumn(['field_key', 'assignment_id']);
        });
    }
};