<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * System Notifications & Alert Rules
     *
     * notifications  — generated notification instances (per-user inbox)
     * alert_rules    — configurable triggers that produce notifications
     *                  (e.g. "alert me when batch expiry < 90 days")
     * alert_rule_recipients — M:M between rules and users/roles who receive the alert
     */
    public function up(): void
    {
        // ── Alert Rules created first (notifications references it) ──
        Schema::create('alert_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('event_type');                        // e.g. "batch_expiry_approaching"
            $table->json('conditions');                          // e.g. {"days_before": 90}
            $table->enum('severity', ['info', 'warning', 'critical'])->default('info');
            $table->boolean('is_active')->default(true);
            $table->enum('frequency', ['once', 'daily', 'weekly', 'on_every_match'])->default('once');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('event_type');
            $table->index('is_active');
        });

        // ── Notifications (depends on alert_rules) ────────────────
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            // Recipient
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Originating alert rule (nullable — system/manual notifications may have none)
            $table->foreignId('alert_rule_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();

            // Classification
            $table->string('type');                              // e.g. "batch_expiry", "po_acknowledged"
            $table->enum('severity', ['info', 'warning', 'critical'])->default('info');

            // Content
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();                    // arbitrary context payload (IDs, amounts, etc.)

            // Polymorphic source — the model that triggered this notification
            $table->string('notifiable_type')->nullable();
            $table->unsignedBigInteger('notifiable_id')->nullable();

            // Action link
            $table->string('action_url')->nullable();
            $table->string('action_label', 80)->nullable();

            // State
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_dismissed')->default(false);
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index(['user_id', 'is_read']);
            $table->index(['notifiable_type', 'notifiable_id'], 'notif_morph_idx');
            $table->index('type');
            $table->index('severity');
            $table->index('created_at');
        });

        Schema::create('alert_rule_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_rule_id')->constrained()->cascadeOnDelete();

            // Either a specific user OR a role (role-based fan-out handled in application layer)
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('role')->nullable();                  // e.g. "finance", "logistics"

            $table->enum('channel', ['in_app', 'email', 'sms'])->default('in_app');
            $table->timestamps();

            $table->unique(['alert_rule_id', 'user_id', 'channel'], 'rule_user_channel_unique');
            $table->index('alert_rule_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_rule_recipients');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('alert_rules');
    }
};
