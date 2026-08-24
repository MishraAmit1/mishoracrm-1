<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->text('service_address')->nullable()->after('notes');

            // Proof-of-visit: one-time browser Geolocation snapshot at each
            // step, not continuous tracking — see AppointmentJobService.
            $table->timestamp('work_started_at')->nullable()->after('service_address');
            $table->decimal('work_started_lat', 10, 7)->nullable()->after('work_started_at');
            $table->decimal('work_started_lng', 10, 7)->nullable()->after('work_started_lat');
            $table->timestamp('work_completed_at')->nullable()->after('work_started_lng');
            $table->decimal('work_completed_lat', 10, 7)->nullable()->after('work_completed_at');
            $table->decimal('work_completed_lng', 10, 7)->nullable()->after('work_completed_lat');

            // Line items consumed on the job — same JSON-array convention as
            // Quotation/Invoice/PurchaseOrder items, optionally product-linked
            // so stock can be decremented via Product::adjustStock().
            $table->json('materials_used')->nullable()->after('work_completed_lng');

            // Customer sign-off — mirrors quotations.signature_data / signed_name.
            $table->longText('customer_signature')->nullable()->after('materials_used');
            $table->string('customer_signed_name')->nullable()->after('customer_signature');
            $table->timestamp('customer_signed_at')->nullable()->after('customer_signed_name');

            $table->foreignId('invoice_id')->nullable()->after('customer_signed_at')->constrained('invoices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropConstrainedForeignId('invoice_id');
            $table->dropColumn([
                'service_address',
                'work_started_at', 'work_started_lat', 'work_started_lng',
                'work_completed_at', 'work_completed_lat', 'work_completed_lng',
                'materials_used',
                'customer_signature', 'customer_signed_name', 'customer_signed_at',
            ]);
        });
    }
};
