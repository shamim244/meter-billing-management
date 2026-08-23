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
        // 1. Email Provider Instances (Registry)
        Schema::create('email_provider_instances', function (Blueprint $table) {
            $table->id();
            $table->string('driver_type', 32); // 'smtp' | 'resend' | 'brevo'
            $table->string('label', 128);      // "Primary Resend", "Backup SMTP"
            $table->text('config');            // Encrypted JSON (API keys, SMTP credentials)
            $table->unsignedInteger('priority')->default(1); // Lower = tried first
            $table->boolean('is_enabled')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->text('last_failure_reason')->nullable();
            $table->timestamps();

            $table->index(['is_enabled', 'priority']);
        });

        // 2. Notifications Master Table
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('event_type', 64)->index();
            $table->string('priority', 20)->default('routine')->index(); // 'critical' | 'routine'
            $table->string('title', 255);
            $table->text('body');
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['user_id', 'read_at']);
            $table->index(['user_id', 'created_at']);
        });

        // 3. Notification Delivery Attempts
        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 32); // 'in_app' | 'email' | 'push'
            $table->foreignId('email_provider_instance_id')->nullable()->constrained('email_provider_instances')->nullOnDelete();
            $table->string('status', 32)->default('pending'); // 'pending' | 'sent' | 'failed' | 'permanently_failed'
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->timestamp('last_attempted_at')->nullable();
            $table->text('failed_reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['notification_id', 'channel']);
            $table->index(['status', 'channel']);
        });

        // 4. Agent Notification Preferences
        Schema::create('agent_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('event_category', 64); // 'billing', 'wallet', 'usage_reports'
            $table->string('channel', 32);        // 'email', 'push'
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'event_category', 'channel'], 'agent_pref_unique');
        });

        // 5. Admin-Editable Notification Templates
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 64);
            $table->string('channel', 32)->default('email'); // 'email', 'in_app'
            $table->string('subject', 255)->nullable();
            $table->text('body_template');
            $table->string('priority', 20)->default('routine'); // 'critical' | 'routine'
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['event_type', 'channel'], 'template_event_channel_unique');
            $table->index(['event_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('agent_notification_preferences');
        Schema::dropIfExists('notification_deliveries');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('email_provider_instances');
    }
};
