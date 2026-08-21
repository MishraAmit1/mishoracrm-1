<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // A "Package" is a Service row that bundles other Services —
            // reuses the exact same rate/tax/billing_cycle/duration/
            // total_quantity fields, so it needs no separate model and
            // works everywhere a Service already does (invoices,
            // quotations, subscriptions, reminders, usage tracking).
            $table->boolean('is_package')->default(false)->after('total_quantity');
        });

        Schema::create('service_package_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('package_service_id')->constrained('services')->onDelete('cascade');
            $table->foreignId('component_service_id')->constrained('services')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['package_service_id', 'component_service_id'], 'package_component_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_package_items');

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('is_package');
        });
    }
};
