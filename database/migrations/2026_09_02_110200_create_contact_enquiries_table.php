<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Platform-level (Milan CRM sales) enquiries submitted from the public
// /contact-sales page — the "Talk to sales" flow of the Enterprise plan.
// Not tenant-scoped: these are leads for the platform owner, like `plans`.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('team_size')->nullable();
            $table->text('message')->nullable();
            $table->string('status')->default('new'); // new | contacted | closed
            $table->string('ip', 45)->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_enquiries');
    }
};
