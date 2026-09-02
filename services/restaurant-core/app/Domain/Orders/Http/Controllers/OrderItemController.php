<?php

namespace App\Domain\Orders\Http\Controllers;

use App\Domain\Orders\Http\Requests\ReasonRequest;
use App\Domain\Orders\Http\Resources\OrderResources;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Orders\Services\OrderService;
use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

class OrderItemController extends Controller
{
    public function __construct(private readonly OrderService $service) {}

    public function cancel(ReasonRequest $request, OrderItem $orderItem): JsonResponse
    {
        return ApiResponse::success(['item' => OrderResources::item($this->service->cancelItem($orderItem, $request->validated('reason')))]);
    }
}
