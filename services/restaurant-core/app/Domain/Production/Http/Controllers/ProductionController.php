<?php

namespace App\Domain\Production\Http\Controllers;

use App\Domain\Production\Http\Requests\AssignPrinterRequest;
use App\Domain\Production\Http\Requests\ProductionCenterRequest;
use App\Domain\Production\Http\Resources\ProductionResources;
use App\Domain\Production\Models\ProductionCenter;
use App\Domain\Production\Services\ProductionConfigurationService;
use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProductionController extends Controller
{
    public function __construct(private readonly ProductionConfigurationService $service) {}

    public function index(): JsonResponse
    {
        return ApiResponse::success(['centers' => $this->service->centers()->map(fn ($center) => ProductionResources::center($center))->all()]);
    }

    public function store(ProductionCenterRequest $request): JsonResponse
    {
        return ApiResponse::success(['center' => ProductionResources::center($this->service->createCenter($request->validated()))], 201);
    }

    public function update(ProductionCenterRequest $request, ProductionCenter $productionCenter): JsonResponse
    {
        return ApiResponse::success(['center' => ProductionResources::center($this->service->updateCenter($productionCenter, $request->validated()))]);
    }

    public function assign(AssignPrinterRequest $request, ProductionCenter $productionCenter): JsonResponse
    {
        return ApiResponse::success(['center' => ProductionResources::center($this->service->assign($productionCenter, $request->validated('printerId'), $request->integer('priority', 1)))]);
    }
}
