<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Contact::$fillable and ContactRequest validation already reference
    // gst_number, but no earlier migration ever added the column — this
    // closes that gap so the CSV importer can actually store it.
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('gst_number')->nullable()->after('pincode');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('gst_number');
        });
    }
};
