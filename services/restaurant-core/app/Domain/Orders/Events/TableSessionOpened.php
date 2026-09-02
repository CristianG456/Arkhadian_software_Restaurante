<?php

namespace App\Domain\Orders\Events;

use Illuminate\Foundation\Events\Dispatchable;

class TableSessionOpened
{
    use Dispatchable;

    public function __construct(public readonly string $sessionId) {}
}
