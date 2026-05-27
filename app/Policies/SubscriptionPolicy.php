<?php

/**
 * Authorization rules for subscriptions.
 * Determines user permissions around subscriptions.
 */

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SubscriptionPolicy
{
    public function view(User $user, Subscription $subscription): Response
    {
        return $user->id === $subscription->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function update(User $user, Subscription $subscription): Response
    {
        return $user->id === $subscription->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
