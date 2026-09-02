<?php

namespace App\Domain\Orders\Events;

use Illuminate\Foundation\Events\Dispatchable;

class OrderCancelled
{
    use Dispatchable;

    public function __construct(public readonly string $orderId) {}
}
