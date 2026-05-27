<?php

/**
 * Command to process recurring subscriptions.
 * Runs billing logic for recurring charges.
 */

namespace App\Console\Commands;

use App\Mail\SubscriptionRenewedMail;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\SubscriptionLifecycleService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class ProcessRecurringSubscriptions extends Command
{
    protected $signature = 'billing:process-recurring-subscriptions';

    protected $description = 'Process recurring billing for active subscriptions';

    public function handle(SubscriptionLifecycleService $lifecycle): int
    {
        $reminders = $lifecycle->sendReminders();
        $renewals = $lifecycle->processRenewals();
        $deleted = $lifecycle->expireAndCleanup();

        $this->info("Lifecycle processed. Reminders: {$reminders}, renewals: {$renewals}, cleaned: {$deleted}");

        return self::SUCCESS;
    }

    private function processSubscription(Subscription $subscription): void
    {
        $billingReference = sprintf(
            'renewal-%s-%s',
            $subscription->id,
            $subscription->next_billing_at?->timestamp ?? now()->timestamp
        );

        $payment = Payment::firstOrCreate(
            [
                'subscription_id' => $subscription->id,
                'provider' => 'internal',
                'provider_payment_id' => $billingReference,
            ],
            [
                'status' => 'paid',
                'amount_cents' => $subscription->plan->price_cents,
                'currency' => strtolower($subscription->plan->currency),
                'paid_at' => now(),
                'payload' => [
                    'renewal' => true,
                    'billing_reference' => $billingReference,
                ],
            ]
        );

        if (! $payment->wasRecentlyCreated) {
            return;
        }

        $newEndsAt = $this->advanceDate($subscription->ends_at, $subscription->plan->billing_interval, $subscription->plan->billing_interval_count);

        $subscription->update([
            'ends_at' => $newEndsAt,
            'next_billing_at' => $newEndsAt,
        ]);

        Mail::to($subscription->user->email)->send(
            new SubscriptionRenewedMail($subscription->fresh(['plan', 'user']))
        );
    }

    private function advanceDate(?Carbon $date, string $interval, int $count): Carbon
    {
        $date = $date?->copy() ?? now();

        return match ($interval) {
            'daily' => $date->addDays($count),
            'weekly' => $date->addWeeks($count),
            'monthly' => $date->addMonthsNoOverflow($count),
            'quarterly' => $date->addMonthsNoOverflow(3 * $count),
            'yearly' => $date->addYears($count),
            default => $date->addDays($count),
        };
    }
}
