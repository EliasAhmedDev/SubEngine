<?php

/**
 * Authorization rules for orders.
 * Encapsulates order-related permission logic.
 */

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrderPolicy
{
    public function view(User $user, Order $order): Response
    {
        return $user->id === $order->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function update(User $user, Order $order): Response
    {
        return $user->id === $order->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function delete(User $user, Order $order): Response
    {
        return $user->id === $order->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
