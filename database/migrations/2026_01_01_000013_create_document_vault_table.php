<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Document Vault
     *
     * A general-purpose document repository for compliance certificates,
     * licences, regulatory filings, GMP audits, and other master documents
     * that are NOT tied to a specific order (those live in order_documents).
     *
     * Supports folder-style categorisation via a self-referencing parent,
     * access-level control, expiry reminders, and version history.
     */
    public function up(): void
    {
        Schema::create('vault_folders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();   // self-reference added after
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('access_level', ['public', 'internal', 'restricted', 'confidential'])->default('internal');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('vault_folders')->nullOnDelete();
        });

        Schema::create('vault_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')
                  ->nullable()
                  ->constrained('vault_folders')
                  ->nullOnDelete();

            // Optional owner: distributor or product registration
            $table->foreignId('distributor_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();
            $table->foreignId('product_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();
            $table->foreignId('country_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();

            // File fields
            $table->string('title');
            $table->string('document_number')->nullable();           // e.g. "GMP-CERT-US-2026"
            $table->string('document_type');                         // e.g. "GMP Certificate", "Export Licence"
            $table->string('file_path');
            $table->string('disk')->default('local');
            $table->string('file_type', 50)->nullable();
            $table->string('extension', 10)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->unsignedSmallInteger('version')->default(1);

            // Dates
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();

            // Alerts
            $table->unsignedSmallInteger('reminder_days_before')->default(30); // send alert N days before expiry
            $table->boolean('expiry_alerted')->default(false);

            // Access
            $table->enum('access_level', ['public', 'internal', 'restricted', 'confidential'])->default('internal');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('folder_id');
            $table->index('document_type');
            $table->index('expiry_date');
            $table->index(['distributor_id', 'document_type']);
            $table->index(['product_id', 'country_id']);
        });

        // Version history: every time a document is replaced, the old version row is archived here
        Schema::create('vault_document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vault_document_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('version');
            $table->string('file_path');
            $table->string('disk')->default('local');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->foreignId('replaced_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('change_notes')->nullable();
            $table->timestamp('replaced_at');
            $table->timestamps();

            $table->index('vault_document_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vault_document_versions');
        Schema::dropIfExists('vault_documents');
        Schema::dropIfExists('vault_folders');
    }
};
