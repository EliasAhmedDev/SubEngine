<?php

/**
 * Handles Stripe webhook callbacks.
 * Receives and parses Stripe webhook events.
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\WebhookEvent;
use App\Notifications\SubscriptionLifecycleNotification;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Throwable;

class StripeWebhookController extends Controller
{
    public function handle(Request $request, StripeService $stripeService)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        if (! $signature || ! $secret) {
            return response()->json([
                'message' => 'Webhook signature missing.',
            ], 400);
        }

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (SignatureVerificationException $e) {
            return response()->json([
                'message' => 'Invalid webhook signature.',
            ], 400);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Invalid webhook payload.',
            ], 400);
        }

        $eventData = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        $webhookEvent = WebhookEvent::query()->firstOrCreate(
            [
                'provider' => 'stripe',
                'provider_event_id' => $event->id,
            ],
            [
                'event_type' => $event->type,
                'payload' => $eventData,
                'attempts' => 1,
            ]
        );

        if ($webhookEvent->processed_at) {
            return response()->json(['received' => true]);
        }

        if (! $webhookEvent->wasRecentlyCreated) {
            $webhookEvent->increment('attempts');
            $webhookEvent->refresh();
        }

        try {
            DB::transaction(function () use ($event, $stripeService, $webhookEvent) {
                $lockedEvent = WebhookEvent::query()
                    ->whereKey($webhookEvent->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedEvent->processed_at) {
                    return;
                }

                if ($event->type !== 'checkout.session.completed') {
                    $lockedEvent->update([
                        'processed_at' => now(),
                    ]);

                    return;
                }

                $session = $event->data->object;
                $subscriptionId = $session->metadata->subscription_id ?? null;

                if (! $subscriptionId) {
                    throw new \RuntimeException('Missing subscription metadata.');
                }

                $subscription = Subscription::query()
                    ->with('plan', 'user')
                    ->find($subscriptionId);

                if (! $subscription) {
                    throw new \RuntimeException('Subscription not found.');
                }

                $paymentIntent = null;

                if (! empty($session->payment_intent)) {
                    try {
                        $paymentIntent = $stripeService->retrievePaymentIntent($session->payment_intent);
                    } catch (Throwable $e) {
                        report($e);
                    }
                }

                $wasPending = $subscription->status === 'pending';

                $subscription->payments()->updateOrCreate(
                    [
                        'billing_cycle_key' => "checkout:{$session->id}",
                    ],
                    [
                        'provider' => 'stripe',
                        'provider_payment_id' => $paymentIntent?->id ?? $session->payment_intent ?? $session->id,
                        'status' => 'paid',
                        'amount_cents' => $subscription->plan->price_cents,
                        'currency' => strtolower($subscription->plan->currency),
                        'paid_at' => now(),
                        'payload' => [
                            'checkout_session_id' => $session->id,
                            'payment_intent_id' => $paymentIntent?->id ?? $session->payment_intent ?? null,
                            'payment_method_id' => $paymentIntent?->payment_method ?? null,
                            'payment_status' => $session->payment_status ?? null,
                            'customer_email' => $session->customer_details->email ?? null,
                        ],
                    ]
                );

                $subscription->update([
                    'status' => 'active',
                    'stripe_customer_id' => $session->customer ?? $subscription->stripe_customer_id,
                    'stripe_payment_method_id' => $paymentIntent?->payment_method ?? $subscription->stripe_payment_method_id,
                    'pending_expires_at' => null,
                    'renewal_failed_at' => null,
                    'reminder_3_days_sent_at' => null,
                    'reminder_1_day_sent_at' => null,
                ]);

                if ($wasPending) {
                    $subscription->user->notify(
                        new SubscriptionLifecycleNotification(
                            'Your subscription is active',
                            'Subscription activated',
                            'Your payment was successful and your subscription is now active.'
                        )
                    );
                }

                $lockedEvent->update([
                    'event_type' => $event->type,
                    'processed_at' => now(),
                    'failure_reason' => null,
                    'failed_at' => null,
                ]);
            });
        } catch (Throwable $e) {
            $webhookEvent->update([
                'failed_at' => now(),
                'failure_reason' => $e->getMessage(),
            ]);

            report($e);

            return response()->json([
                'message' => 'Webhook processing failed.',
            ], 500);
        }

        return response()->json(['received' => true]);
    }
}
