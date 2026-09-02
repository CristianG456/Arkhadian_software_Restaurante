<?php

namespace App\Domain\Production\Http\Controllers;

use App\Domain\Orders\Enums\OrderSource;
use App\Domain\Orders\Models\TableSession;
use App\Domain\Production\Http\Resources\ProductionResources;
use App\Domain\Production\Services\PreaccountService;
use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

class PreaccountController extends Controller
{
    public function __construct(private readonly PreaccountService $service) {}

    public function preview(TableSession $tableSession): JsonResponse
    {
        return ApiResponse::success(['preaccount' => $this->service->preview($tableSession)]);
    }

    public function print(TableSession $tableSession): JsonResponse
    {
        return ApiResponse::success(['printJob' => ProductionResources::job($this->service->print($tableSession)->load(['printer', 'center']))], 201);
    }

    public function requestBill(TableSession $tableSession): JsonResponse
    {
        $session = $this->service->requestBill($tableSession, OrderSource::ADMIN_POS);

        return ApiResponse::success(['billRequestedAt' => $session->bill_requested_at->toIso8601String(), 'source' => $session->bill_request_source]);
    }
}
