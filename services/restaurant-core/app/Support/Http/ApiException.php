<?php

namespace App\Support\Http;

use Exception;
use Throwable;

/**
 * Throw this when a request fails for a reason the caller should be able
 * to branch on programmatically (a stable `code`), not just read as text.
 */
class ApiException extends Exception
{
    public function __construct(
        private readonly string $code,
        string $message,
        private readonly int $status = 400,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function code(): string
    {
        return $this->code;
    }

    public function status(): int
    {
        return $this->status;
    }
}
