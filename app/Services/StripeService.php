<?php

/**
 * Stripe integration helpers.
 * Encapsulates Stripe API interactions and helpers.
 */

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

class StripeService
{
    private StripeClient $client;

    public function __construct()
    {
        $this->client = new StripeClient(config('services.stripe.secret'));
    }

    public function createCheckoutSession(
        Subscription $subscription,
        Plan $plan,
        User $user
    ): Session {
        $customer = $this->client->customers->create([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => [
                'user_id' => (string) $user->id,
                'subscription_id' => (string) $subscription->id,
            ],
        ], [
            'idempotency_key' => "customer:{$user->id}:{$subscription->id}",
        ]);

        $successUrl = rtrim(config('app.url'), '/').'/api/payments/success?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = rtrim(config('app.url'), '/').'/api/payments/cancel';

        return $this->client->checkout->sessions->create([
            'mode' => 'payment',
            'customer' => $customer->id,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'payment_intent_data' => [
                'setup_future_usage' => 'off_session',
            ],
            'line_items' => [
                [
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => strtolower($plan->currency),
                        'unit_amount' => $plan->price_cents,
                        'product_data' => [
                            'name' => $plan->name,
                            'description' => $plan->description,
                        ],
                    ],
                ],
            ],
            'metadata' => [
                'subscription_id' => $subscription->id,
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'plan_slug' => $plan->slug,
            ],
        ], [
            'idempotency_key' => "checkout:{$subscription->id}",
        ]);
    }

    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return $this->client->paymentIntents->retrieve($paymentIntentId, []);
    }

    public function chargeRecurringSubscription(Subscription $subscription, string $billingCycleKey): PaymentIntent
    {
        if (! $subscription->stripe_customer_id || ! $subscription->stripe_payment_method_id) {
            throw new \RuntimeException('Missing Stripe billing credentials.');
        }

        return $this->client->paymentIntents->create([
            'amount' => $subscription->plan->price_cents,
            'currency' => strtolower($subscription->plan->currency),
            'customer' => $subscription->stripe_customer_id,
            'payment_method' => $subscription->stripe_payment_method_id,
            'off_session' => true,
            'confirm' => true,
            'metadata' => [
                'subscription_id' => $subscription->id,
                'billing_cycle_key' => $billingCycleKey,
            ],
        ], [
            'idempotency_key' => $billingCycleKey,
        ]);
    }
}
