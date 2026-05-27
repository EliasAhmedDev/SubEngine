<?php

/**
 * API controller for subscriptions.
 * CRUD and listing endpoints for subscriptions.
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubscriptionRequest;
use App\Http\Requests\UpdateSubscriptionRequest;
use App\Http\Resources\SubscriptionResource;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\StripeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Throwable;

class SubscriptionController extends Controller
{
    public function show(Request $request)
    {
        $subscription = $this->currentSubscription($request);

        if (! $subscription) {
            return response()->json([
                'message' => 'No active subscription found.',
            ], 404);
        }

        $this->authorize('view', $subscription);

        return new SubscriptionResource($subscription->load('plan'));
    }

    public function store(StoreSubscriptionRequest $request, StripeService $stripeService)
    {
        $validated = $request->validated();

        $activeSubscription = $request->user()
            ->subscriptions()
            ->where('status', 'active')
            ->first();

        if ($activeSubscription) {
            return response()->json([
                'message' => 'You already have an active subscription.',
            ], HttpResponse::HTTP_CONFLICT);
        }

        $plan = Plan::query()
            ->where('slug', $validated['plan_slug'])
            ->where('is_active', true)
            ->firstOrFail();

        $request->user()
            ->subscriptions()
            ->where('status', 'pending')
            ->get()
            ->each(function ($pendingSubscription) {

                $pendingSubscription->payments()
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'canceled',
                        'canceled_at' => now(),
                    ]);

                $pendingSubscription->delete();
            });
        $startsAt = now();
        $endsAt = $this->calculateEndsAt($startsAt, $plan);

        $subscription = DB::transaction(function () use ($request, $validated, $plan, $startsAt, $endsAt) {
            return $request->user()->subscriptions()->create([
                'plan_id' => $plan->id,
                'status' => 'pending',
                'auto_renew' => $validated['auto_renew'],
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'next_billing_at' => $endsAt,
                'renewal_due_at' => $this->calculateRenewalDueAt($endsAt),
                'pending_expires_at' => now()->addDay(),
                'metadata' => [],
            ]);
        });

        try {
            $session = $stripeService->createCheckoutSession(
                $subscription->load('plan'),
                $plan,
                $request->user()
            );
        } catch (Throwable $e) {
            $subscription->payments()
                ->where('status', 'pending')
                ->update([
                    'status' => 'canceled',
                    'canceled_at' => now(),
                ]);

            $subscription->delete();

            throw $e;
        }

        $subscription->update([
            'stripe_customer_id' => $session->customer ?? null,
        ]);

        $subscription->payments()
            ->where('status', 'pending')
            ->whereNull('canceled_at')
            ->update([
                'status' => 'canceled',
                'canceled_at' => now(),
            ]);

        $subscription->payments()->create([
            'billing_cycle_key' => "checkout:{$session->id}",
            'provider' => 'stripe',
            'provider_payment_id' => $session->id,
            'status' => 'pending',
            'amount_cents' => $plan->price_cents,
            'currency' => strtolower($plan->currency),
            'paid_at' => null,
            'payload' => [
                'checkout_session_id' => $session->id,
                'checkout_url' => $session->url,
            ],
        ]);

        return response()->json([
            'message' => 'Checkout session created.',
            'checkout_url' => $session->url,
            'subscription' => new SubscriptionResource($subscription->load('plan')),
        ], HttpResponse::HTTP_CREATED, [], JSON_UNESCAPED_SLASHES);
    }

    public function update(UpdateSubscriptionRequest $request)
    {
        $subscription = $this->currentSubscription($request);

        if (! $subscription) {
            return response()->json([
                'message' => 'No active subscription found.',
            ], 404);
        }

        $this->authorize('update', $subscription);

        $subscription->update([
            'auto_renew' => $request->boolean('auto_renew'),
        ]);

        return new SubscriptionResource($subscription->load('plan'));
    }

    private function currentSubscription(Request $request): ?Subscription
    {
        return $request->user()
            ->subscriptions()
            ->current()
            ->with('plan')
            ->latest('starts_at')
            ->first();
    }

    private function calculateEndsAt(Carbon $startsAt, Plan $plan): Carbon
    {
        return match ($plan->billing_interval) {
            'daily' => $startsAt->copy()->addDays($plan->billing_interval_count),
            'weekly' => $startsAt->copy()->addWeeks($plan->billing_interval_count),
            'monthly' => $startsAt->copy()->addMonthsNoOverflow($plan->billing_interval_count),
            'quarterly' => $startsAt->copy()->addMonthsNoOverflow(3 * $plan->billing_interval_count),
            'yearly' => $startsAt->copy()->addYears($plan->billing_interval_count),
            default => abort(422, 'Invalid billing interval.'),
        };
    }

    private function calculateRenewalDueAt(Carbon $endsAt): Carbon
    {
        return $endsAt->copy()->startOfDay()->subDay();
    }
}
