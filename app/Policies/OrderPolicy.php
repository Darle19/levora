<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $order->agency_id === $user->agency_id;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }
}
