<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('provider')->index();
            $table->string('provider_event_id')->unique();
            $table->string('event_type')->index();
            $table->json('payload');
            $table->unsignedInteger('attempts')->default(1);
            $table->timestampTz('processed_at')->nullable()->index();
            $table->timestampTz('failed_at')->nullable()->index();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
