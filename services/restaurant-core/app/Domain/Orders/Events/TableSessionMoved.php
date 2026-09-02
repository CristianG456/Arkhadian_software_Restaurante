<?php

namespace App\Domain\Orders\Events;

use Illuminate\Foundation\Events\Dispatchable;

class TableSessionMoved
{
    use Dispatchable;

    public function __construct(public readonly string $sessionId, public readonly string $fromTableId, public readonly string $toTableId) {}
}
