<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->softDeletes();

            $table->string('stripe_customer_id')->nullable()->index()->after('metadata');
            $table->string('stripe_payment_method_id')->nullable()->index()->after('stripe_customer_id');

            $table->timestampTz('renewal_due_at')->nullable()->index()->after('next_billing_at');
            $table->timestampTz('pending_expires_at')->nullable()->index()->after('status');

            $table->timestampTz('reminder_3_days_sent_at')->nullable()->after('pending_expires_at');
            $table->timestampTz('reminder_1_day_sent_at')->nullable()->after('reminder_3_days_sent_at');
            $table->timestampTz('renewal_failed_at')->nullable()->after('reminder_1_day_sent_at');
            $table->timestampTz('expired_at')->nullable()->after('renewal_failed_at');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropSoftDeletes();

            $table->dropColumn([
                'stripe_customer_id',
                'stripe_payment_method_id',
                'renewal_due_at',
                'pending_expires_at',
                'reminder_3_days_sent_at',
                'reminder_1_day_sent_at',
                'renewal_failed_at',
                'expired_at',
            ]);
        });
    }
};
