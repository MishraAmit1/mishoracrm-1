<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_call_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('lead_id');
            $table->string('type', 30)->default('note'); // call, note, email, meeting, whatsapp
            $table->text('description');
            $table->string('call_outcome', 50)->nullable(); // connected, no_answer, voicemail, callback, not_interested
            $table->unsignedSmallInteger('call_duration')->nullable(); // minutes
            $table->timestamp('logged_at')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->index(['tenant_id', 'lead_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_call_logs');
    }
};
