<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

            // Who gets this notification
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Who triggered it
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();

            // Notification type — lead.created, deal.won, followup.due etc.
            $table->string('type');

            // Title + message
            $table->string('title');
            $table->text('message');

            // Link to navigate when clicked
            $table->string('url')->nullable();

            // Icon / color
            $table->string('icon')->default('bell');
            $table->string('color')->default('accent');

            // Related model (polymorphic-like)
            $table->string('notifiable_type')->nullable(); // App\Models\Lead
            $table->unsignedBigInteger('notifiable_id')->nullable();

            // Read status
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();

            // Channels already sent on
            $table->json('channels_sent')->nullable(); // ['database','whatsapp','email']

            $table->timestamps();

            $table->index(['tenant_id', 'user_id', 'is_read']);
            $table->index(['tenant_id', 'type']);
            $table->index(['notifiable_type', 'notifiable_id']);
        });

        // Notification preferences per user
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type');                        // lead.created
            $table->boolean('in_app')->default(true);      // bell notification
            $table->boolean('email')->default(false);
            $table->boolean('whatsapp')->default(false);
            $table->boolean('slack')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notifications');
    }
};