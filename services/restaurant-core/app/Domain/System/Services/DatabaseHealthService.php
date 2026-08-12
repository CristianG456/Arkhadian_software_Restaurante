<?php

namespace App\Domain\System\Services;

use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Checked explicitly (never left to bubble up as an unhandled exception)
 * because a down database must render as a status row, not a crashed
 * request — Restaurant Core has to keep answering /api/system/status even
 * when PostgreSQL is unreachable.
 */
final class DatabaseHealthService
{
    /**
     * @return array{status: 'connected'|'disconnected', latencyMs: float|null}
     */
    public function check(): array
    {
        $start = microtime(true);

        try {
            DB::connection()->select('select 1');

            return [
                'status' => 'connected',
                'latencyMs' => round((microtime(true) - $start) * 1000, 1),
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'status' => 'disconnected',
                'latencyMs' => null,
            ];
        }
    }
}
