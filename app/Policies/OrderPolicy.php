<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Order $order): bool
    {
        return $order->user_id === $user->id;
    }

    public function pay(User $user, Order $order): bool
    {
        return $order->user_id === $user->id && $order->status->canReceivePayment();
    }

    public function manageAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function manage(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }

    public function refund(User $user, Order $order): bool
    {
        return $user->isAdmin() && in_array($order->status, [
            OrderStatus::Paid,
            OrderStatus::PartiallyRefunded,
        ], true);
    }
}
