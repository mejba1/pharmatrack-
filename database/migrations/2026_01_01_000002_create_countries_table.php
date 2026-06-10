<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Countries & regulatory permissions
     * (drives country_permissions page + distribution hierarchy)
     */
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique();                // ISO 3166-1 alpha-2/3  e.g. "PH", "BD"
            $table->string('name');                             // e.g. "Philippines"
            $table->string('region')->nullable();               // e.g. "South-East Asia"
            $table->string('currency_code', 3)->nullable();     // e.g. "PHP"

            // Regulatory gate-keeping (countries page)
            $table->boolean('import_permitted')->default(true);
            $table->boolean('import_license_required')->default(false);
            $table->boolean('gmp_certificate_required')->default(false);
            $table->boolean('product_registration_required')->default(true);
            $table->string('regulatory_authority')->nullable(); // e.g. "FDA Philippines"
            $table->enum('regulatory_status', [
                'approved',
                'restricted',
                'pending',
                'banned',
            ])->default('approved');

            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('region');
            $table->index('regulatory_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
