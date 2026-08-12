<?php

namespace App\Domain\System\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * A liveness probe, not a health report: answers "is Core's HTTP layer
 * responding at all", independent of database or business-logic health.
 * Kept flat (no success/error envelope) since it follows the conventional
 * shape monitoring tools expect from a "/health" endpoint. Use
 * GET /api/system/status for the full picture (see SystemStatusController).
 */
final class HealthController
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'restaurant-core',
            'version' => config('system.version'),
        ]);
    }
}
