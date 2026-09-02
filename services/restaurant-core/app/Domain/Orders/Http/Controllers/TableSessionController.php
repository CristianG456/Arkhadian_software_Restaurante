<?php

namespace App\Domain\Orders\Http\Controllers;

use App\Domain\Orders\Http\Requests\MoveSessionRequest;
use App\Domain\Orders\Http\Requests\TransferWaiterRequest;
use App\Domain\Orders\Http\Resources\OrderResources;
use App\Domain\Orders\Models\TableSession;
use App\Domain\Orders\Services\TableSessionService;
use App\Domain\Tables\Models\RestaurantTable;
use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

class TableSessionController extends Controller
{
    public function __construct(private readonly TableSessionService $service) {}

    public function show(TableSession $tableSession): JsonResponse
    {
        return ApiResponse::success(['session' => OrderResources::session($this->service->hydrate($tableSession), $this->service)]);
    }

    public function current(RestaurantTable $table): JsonResponse
    {
        $session = $this->service->current($table);

        return ApiResponse::success(['session' => $session ? OrderResources::session($session, $this->service) : null]);
    }

    public function transferWaiter(TransferWaiterRequest $request, TableSession $tableSession): JsonResponse
    {
        return ApiResponse::success(['session' => OrderResources::session($this->service->transferWaiter($tableSession, $request->validated('waiterId'), $request->validated('reason')), $this->service)]);
    }

    public function move(MoveSessionRequest $request, TableSession $tableSession): JsonResponse
    {
        return ApiResponse::success(['session' => OrderResources::session($this->service->move($tableSession, $request->validated('targetTableId'), $request->validated('reason')), $this->service)]);
    }
}
