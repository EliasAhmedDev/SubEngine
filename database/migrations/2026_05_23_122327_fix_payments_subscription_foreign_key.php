<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['subscription_id']);

            $table->foreignUlid('subscription_id')
                ->nullable()
                ->change();

            $table->foreign('subscription_id')
                ->references('id')
                ->on('subscriptions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['subscription_id']);

            $table->foreignUlid('subscription_id')
                ->nullable(false)
                ->change();

            $table->foreign('subscription_id')
                ->references('id')
                ->on('subscriptions')
                ->restrictOnDelete();
        });
    }
};
