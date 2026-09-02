<?php

namespace App\Domain\Orders\Events;

use Illuminate\Foundation\Events\Dispatchable;

class TableWaiterTransferred
{
    use Dispatchable;

    public function __construct(
        public readonly string $sessionId,
        public readonly string $areaName,
        public readonly string $tableLabel,
        public readonly string $fromWaiterId,
        public readonly string $fromWaiterName,
        public readonly string $toWaiterId,
        public readonly string $toWaiterName,
        public readonly string $occurredAt,
    ) {}
}
