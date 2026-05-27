<?php

/**
 * Handles subscription lifecycle tasks.
 * Contains business logic for subscription state changes.
 */

namespace App\Services;

use App\Models\Subscription;
use App\Notifications\SubscriptionLifecycleNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class SubscriptionLifecycleService
{
    public function __construct(private readonly StripeService $stripeService) {}

    public function sendReminders(): int
    {
        $sent = 0;

        foreach ([3 => 'reminder_3_days_sent_at', 1 => 'reminder_1_day_sent_at'] as $daysLeft => $column) {
            $subscriptions = Subscription::query()
                ->where('status', 'active')
                ->whereDate('ends_at', now()->addDays($daysLeft)->toDateString())
                ->whereNull($column)
                ->with(['plan', 'user'])
                ->get();

            foreach ($subscriptions as $subscription) {
                try {
                    $subject = $subscription->auto_renew
                        ? "Your subscription renews in {$daysLeft} day".($daysLeft === 1 ? '' : 's')
                        : "Your subscription expires in {$daysLeft} day".($daysLeft === 1 ? '' : 's');

                    $headline = $subscription->auto_renew
                        ? 'Upcoming renewal reminder'
                        : 'Upcoming expiry reminder';

                    $body = $subscription->auto_renew
                        ? "Your subscription will renew in {$daysLeft} day".($daysLeft === 1 ? '' : 's').'.'
                        : "Your subscription will expire in {$daysLeft} day".($daysLeft === 1 ? '' : 's').'.';

                    $subscription->user->notify(
                        new SubscriptionLifecycleNotification($subject, $headline, $body)
                    );

                    $subscription->update([$column => now()]);
                    $sent++;
                } catch (Throwable $e) {
                    report($e);
                }
            }
        }

        return $sent;
    }

    public function processRenewals(): int
    {
        $processed = 0;

        $subscriptions = Subscription::query()
            ->renewalDue()
            ->with(['plan', 'user'])
            ->get();

        foreach ($subscriptions as $subscription) {
            $this->renew($subscription);
            $processed++;
        }

        return $processed;
    }

    public function expireAndCleanup(): int
    {
        $deleted = 0;

        $expiring = Subscription::query()
            ->expired()
            ->with(['plan', 'user'])
            ->get();

        foreach ($expiring as $subscription) {
            try {
                $subscription->user->notify(
                    new SubscriptionLifecycleNotification(
                        'Your subscription has expired',
                        'Subscription expired',
                        'Your subscription has expired and has been removed from active access.'
                    )
                );
            } catch (Throwable $e) {
                report($e);
            }

            $subscription->forceFill([
                'status' => 'expired',
                'expired_at' => now(),
            ])->save();

            $subscription->delete();
            $deleted++;
        }

        $stalePending = Subscription::query()
            ->stalePending()
            ->with(['plan', 'user'])
            ->get();

        foreach ($stalePending as $subscription) {
            $subscription->delete();
            $deleted++;
        }

        return $deleted;
    }

    private function renew(Subscription $subscription): void
    {
        $billingCycleKey = sprintf(
            'renewal:%s:%s',
            $subscription->id,
            $subscription->renewal_due_at?->format('Ymd') ?? now()->format('Ymd')
        );

        try {
            $paymentIntent = $this->stripeService->chargeRecurringSubscription($subscription, $billingCycleKey);

            DB::transaction(function () use ($subscription, $paymentIntent, $billingCycleKey) {
                $subscription->payments()->updateOrCreate(
                    [
                        'billing_cycle_key' => $billingCycleKey,
                    ],
                    [
                        'provider' => 'stripe',
                        'provider_payment_id' => $paymentIntent->id,
                        'status' => 'paid',
                        'amount_cents' => $subscription->plan->price_cents,
                        'currency' => strtolower($subscription->plan->currency),
                        'paid_at' => now(),
                        'payload' => [
                            'payment_intent_id' => $paymentIntent->id,
                            'renewal' => true,
                        ],
                    ]
                );

                $newEndsAt = $this->advanceDate(
                    $subscription->ends_at->copy(),
                    $subscription->plan->billing_interval,
                    $subscription->plan->billing_interval_count
                );

                $subscription->update([
                    'status' => 'active',
                    'ends_at' => $newEndsAt,
                    'next_billing_at' => $newEndsAt,
                    'renewal_due_at' => $this->calculateRenewalDueAt($newEndsAt),
                    'reminder_3_days_sent_at' => null,
                    'reminder_1_day_sent_at' => null,
                    'renewal_failed_at' => null,
                    'auto_renew' => true,
                ]);
            });

            try {
                $subscription->user->notify(
                    new SubscriptionLifecycleNotification(
                        'Your subscription was renewed',
                        'Renewal successful',
                        'Your subscription renewed successfully. Your next billing date has been updated.'
                    )
                );
            } catch (Throwable $e) {
                report($e);
            }
        } catch (Throwable $e) {
            DB::transaction(function () use ($subscription, $billingCycleKey, $e) {
                $subscription->payments()->updateOrCreate(
                    [
                        'billing_cycle_key' => $billingCycleKey,
                    ],
                    [
                        'provider' => 'stripe',
                        'provider_payment_id' => "failed:{$billingCycleKey}",
                        'status' => 'failed',
                        'amount_cents' => $subscription->plan->price_cents,
                        'currency' => strtolower($subscription->plan->currency),
                        'paid_at' => null,
                        'payload' => [
                            'renewal' => true,
                            'error' => class_basename($e),
                            'message' => $e->getMessage(),
                        ],
                    ]
                );

                $subscription->update([
                    'auto_renew' => false,
                    'renewal_failed_at' => now(),
                ]);
            });

            try {
                $subscription->user->notify(
                    new SubscriptionLifecycleNotification(
                        'Subscription renewal failed',
                        'Renewal failed',
                        'We could not renew your subscription. Auto-renew has been turned off and the subscription will expire at the end of the current cycle.'
                    )
                );
            } catch (Throwable $mailException) {
                report($mailException);
            }
        }
    }

    private function advanceDate(Carbon $date, string $interval, int $count): Carbon
    {
        return match ($interval) {
            'daily' => $date->addDays($count),
            'weekly' => $date->addWeeks($count),
            'monthly' => $date->addMonthsNoOverflow($count),
            'quarterly' => $date->addMonthsNoOverflow(3 * $count),
            'yearly' => $date->addYears($count),
            default => $date->addDays($count),
        };
    }

    private function calculateRenewalDueAt(Carbon $endsAt): Carbon
    {
        return $endsAt->copy()->startOfDay()->subDay();
    }
}
