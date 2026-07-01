<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** In-app notifications for the customer portal (isolated from staff table). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('type', 40)->default('info');
            $table->string('title');
            $table->string('message')->nullable();
            $table->string('icon', 40)->default('bi-bell');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_notifications');
    }
};
