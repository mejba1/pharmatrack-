<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reports Module
     *
     * report_definitions  — saved report templates (filters, columns, chart type)
     * report_runs         — each execution of a report (scheduled or on-demand)
     * report_schedules    — cron-like schedule configuration for automated runs
     *
     * Generated files (PDF, XLSX, CSV) are stored on disk and referenced
     * via report_runs.output_path.
     */
    public function up(): void
    {
        Schema::create('report_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // Module grouping shown in the UI sidebar
            $table->enum('module', [
                'orders',
                'shipments',
                'inventory',
                'finance',
                'compliance',
                'patients',
                'anti_counterfeit',
                'custom',
            ])->default('custom');

            $table->string('report_type');                       // e.g. "tabular", "summary", "chart"
            $table->json('default_filters')->nullable();         // serialised filter state
            $table->json('columns')->nullable();                 // selected / ordered columns
            $table->json('sort')->nullable();                    // [{column, direction}]
            $table->string('chart_type')->nullable();            // "bar", "line", "pie" …
            $table->boolean('is_public')->default(false);        // visible to all users vs private
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['module', 'is_active']);
            $table->index('created_by');
        });

        Schema::create('report_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_definition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('run_by')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('trigger', ['manual', 'scheduled'])->default('manual');
            $table->json('applied_filters')->nullable();          // snapshot of filters used for this run

            $table->enum('status', ['queued', 'processing', 'completed', 'failed'])->default('queued');
            $table->string('output_format', 10)->nullable();      // "pdf", "xlsx", "csv"
            $table->string('output_path')->nullable();
            $table->string('disk')->default('local');
            $table->unsignedBigInteger('output_size')->nullable(); // bytes

            $table->unsignedInteger('row_count')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('report_definition_id');
            $table->index('status');
            $table->index('run_by');
        });

        Schema::create('report_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_definition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('cron_expression');                    // e.g. "0 8 * * 1" (Mon 8am)
            $table->string('timezone')->default('UTC');
            $table->string('output_format', 10)->default('pdf');
            $table->json('filters')->nullable();                  // override default filters for scheduled run

            // Delivery
            $table->boolean('email_recipients')->default(false);
            $table->json('recipient_emails')->nullable();         // ["a@b.com", ...]
            $table->boolean('is_active')->default(true);

            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->timestamps();

            $table->index('report_definition_id');
            $table->index(['is_active', 'next_run_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_schedules');
        Schema::dropIfExists('report_runs');
        Schema::dropIfExists('report_definitions');
    }
};
