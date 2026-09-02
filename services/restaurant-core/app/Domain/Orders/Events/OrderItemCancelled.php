<?php

namespace App\Domain\Orders\Events;

use Illuminate\Foundation\Events\Dispatchable;

class OrderItemCancelled
{
    use Dispatchable;

    public function __construct(public readonly string $itemId) {}
}
