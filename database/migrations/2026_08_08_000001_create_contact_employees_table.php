<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('contact_id')->constrained()->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('designation')->nullable();
            $table->json('emails')->nullable();   // multiple emails
            $table->json('phones')->nullable();   // multiple contact numbers
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('contact_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_employees');
    }
};
