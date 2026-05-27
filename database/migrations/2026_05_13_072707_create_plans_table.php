<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('name', 120);
            $table->string('slug', 120)->unique();
            $table->text('description')->nullable();

            $table->unsignedBigInteger('price_cents');
            $table->char('currency', 3)->default('USD');

            $table->string('billing_interval', 20); // monthly, yearly, weekly, etc.
            $table->unsignedSmallInteger('billing_interval_count')->default(1);

            $table->unsignedSmallInteger('trial_days')->default(0);
            $table->boolean('is_active')->default(true)->index();

            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['billing_interval', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
