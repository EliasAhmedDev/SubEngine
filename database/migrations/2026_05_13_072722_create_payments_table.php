<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('provider', 50)->index(); // stripe, dummy, etc.

            $table->string('provider_payment_id', 120)->nullable()->unique();

            $table->string('status', 20)->default('pending')->index();

            $table->unsignedBigInteger('amount_cents');
            $table->char('currency', 3)->default('USD');

            $table->timestampTz('paid_at')->nullable();

            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['provider', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
