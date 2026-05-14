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
        Schema::create('tenants', function (Blueprint $table) {
             $table->id();
             $table->string('name');
             $table->string('subdomain')->unique();   // demo.yourcrm.com
             $table->string('email')->unique();
             $table->string('phone')->nullable();
             $table->string('logo')->nullable();
             $table->string('timezone')->default('Asia/Kolkata');
             $table->string('currency')->default('INR');
             $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
             $table->json('settings')->nullable();
             $table->timestamps();
             $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
