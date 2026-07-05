<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Verification access-control policies. A policy targets a scope
     * (product / batch / one-or-more specific UUC codes) and constrains how
     * that scope may be verified:
     *   • locked            — block verification entirely
     *   • allowed_countries — only these country codes may verify (empty = any)
     *   • allowed_cities    — only these cities may verify (empty = any)
     *   • scan_limit        — max total verifications allowed for the scope
     *   • device_limit      — max distinct devices allowed for the scope
     */
    public function up(): void
    {
        Schema::create('verification_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('scope_type');                 // product | batch | codes
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->json('uuc_codes')->nullable();        // for scope_type = codes

            $table->boolean('locked')->default(false);
            $table->json('allowed_countries')->nullable(); // ['BD','NG'] — empty/null = all
            $table->json('allowed_cities')->nullable();    // ['Dhaka'] — empty/null = all
            $table->unsignedInteger('scan_limit')->nullable();
            $table->unsignedInteger('device_limit')->nullable();

            $table->boolean('active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['scope_type', 'active']);
            $table->index(['batch_id', 'active']);
            $table->index(['product_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_policies');
    }
};
