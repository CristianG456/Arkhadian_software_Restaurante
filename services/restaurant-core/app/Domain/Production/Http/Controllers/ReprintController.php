<?php

namespace App\Domain\Production\Http\Controllers;

use App\Domain\Orders\Models\Order;
use App\Domain\Production\Http\Resources\ProductionResources;
use App\Domain\Production\Services\ProductionRoutingService;
use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReprintController extends Controller
{
    public function __invoke(Request $request, Order $order, ProductionRoutingService $service): JsonResponse
    {
        $data = $request->validate([
            'productionCenterId' => ['nullable', 'ulid', 'exists:production_centers,id'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);
        $jobs = collect($service->reprint($order, $data['productionCenterId'] ?? null, $data['reason'] ?? null))
            ->map(fn ($job) => ProductionResources::job($job->load(['printer', 'center'])))
            ->all();

        return ApiResponse::success(['printJobs' => $jobs], 201);
    }
}
