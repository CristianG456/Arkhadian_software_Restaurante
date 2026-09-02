<?php

namespace App\Domain\Orders\Http\Controllers;

use App\Domain\Orders\Http\Requests\ReasonRequest;
use App\Domain\Orders\Http\Requests\StoreOrderRequest;
use App\Domain\Orders\Http\Resources\OrderResources;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Services\OrderService;
use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $service) {}

    public function store(StoreOrderRequest $request): JsonResponse
    {
        return ApiResponse::success(['order' => OrderResources::order($this->service->createAdmin($request->validated()))], 201);
    }

    public function show(Order $order): JsonResponse
    {
        return ApiResponse::success(['order' => OrderResources::order($this->service->hydrate($order))]);
    }

    public function cancel(ReasonRequest $request, Order $order): JsonResponse
    {
        return ApiResponse::success(['order' => OrderResources::order($this->service->cancelOrder($order, $request->validated('reason')))]);
    }
}
