<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Order Documents — polymorphic reference document attachments.
     *
     * The "Reference Documents" tab in the PO / SO / PI / CI view modals
     * maps 1-to-many onto this single table using a polymorphic key pair:
     *   documentable_type  →  "App\Models\PurchaseOrder" | "SalesOrder" | "ProformaInvoice" | "CommercialInvoice"
     *   documentable_id    →  the parent record's primary key
     *
     * Examples from the UI:
     *   PO: Signed PO PDF, Authorization Letter, Product Spec, Regulatory Approval
     *   SO: SO Confirmation, Export Licence, Customer Compliance Cert
     *   PI: Finance Approval Email, Certificate of Analysis, Pro-forma PDF
     *   CI: Signed CI, Packing List (xlsx), Certificate of Origin, COA, Customs Declaration
     */
    public function up(): void
    {
        Schema::create('order_documents', function (Blueprint $table) {
            $table->id();

            // Polymorphic parent
            $table->string('documentable_type');
            $table->unsignedBigInteger('documentable_id');

            // File metadata
            $table->string('name');                              // original filename
            $table->string('category');                         // e.g. "Signed PO", "Regulatory Doc"
            $table->string('file_path');                        // storage path / S3 key
            $table->string('disk')->default('local');           // 'local' | 's3' | 'gcs'
            $table->string('file_type', 50)->nullable();        // MIME type, e.g. "application/pdf"
            $table->string('extension', 10)->nullable();        // e.g. "pdf", "docx", "xlsx"
            $table->unsignedBigInteger('file_size')->nullable(); // bytes
            $table->string('icon_class', 30)->nullable();       // "pdf" | "word" | "excel" | "img" | "other" — drives UI icon colour

            // Upload metadata
            $table->foreignId('uploaded_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // Optional versioning — later files for the same category supersede older ones
            $table->unsignedSmallInteger('version')->default(1);
            $table->text('notes')->nullable();

            $table->boolean('is_active')->default(true);        // soft-toggle without deleting
            $table->timestamps();
            $table->softDeletes();

            // Composite index for the polymorphic lookup
            $table->index(['documentable_type', 'documentable_id'], 'order_docs_morph_idx');
            $table->index('uploaded_by');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_documents');
    }
};
