<?php

namespace App\Domain\Orders\Http\Controllers;

use App\Domain\Orders\Http\Resources\OrderResources;
use App\Domain\Orders\Services\TableSessionService;
use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

class OperationController extends Controller
{
    public function __construct(private readonly TableSessionService $service) {}

    public function __invoke(): JsonResponse
    {
        $sessions = $this->service->openSessions()->keyBy('table_id');
        $areas = $this->service->operations()->map(fn ($area) => ['id' => $area->id, 'name' => $area->name, 'tables' => $area->tables->map(function ($table) use ($sessions) {
            $session = $sessions->get($table->id);

            return ['id' => $table->id, 'label' => $table->label, 'state' => $session ? 'OCCUPIED' : 'FREE', 'session' => $session ? ['id' => $session->id, 'waiter' => OrderResources::staff($session->waiter), 'openedAt' => $session->opened_at->toIso8601String(), 'ordersCount' => $session->confirmed_orders_count, 'total' => (string) $session->operation_total] : null];
        })->values()->all()])->values()->all();

        return ApiResponse::success(['areas' => $areas]);
    }
}
