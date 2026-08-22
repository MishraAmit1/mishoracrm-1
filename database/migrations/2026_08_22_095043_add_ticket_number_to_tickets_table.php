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
        Schema::table('tickets', function (Blueprint $table) {
            // Human-friendly reference number (e.g. TKT-20260822-0001) —
            // the public_token stays the unguessable access-control key for
            // the tracking link; this is what the customer actually quotes
            // over phone/email and types into the "track my ticket" lookup.
            $table->string('ticket_number')->nullable()->unique()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('ticket_number');
        });
    }
};
