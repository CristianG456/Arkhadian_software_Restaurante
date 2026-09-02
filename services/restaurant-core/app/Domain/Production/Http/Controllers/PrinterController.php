<?php

namespace App\Domain\Production\Http\Controllers;

use App\Domain\Production\Http\Requests\PrinterRequest;
use App\Domain\Production\Http\Resources\ProductionResources;
use App\Domain\Production\Models\Printer;
use App\Domain\Production\Services\ProductionConfigurationService;
use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

class PrinterController extends Controller
{
    public function __construct(private readonly ProductionConfigurationService $service) {}

    public function index(): JsonResponse
    {
        return ApiResponse::success(['printers' => $this->service->printers()->map(fn ($printer) => ProductionResources::printer($printer))->all()]);
    }

    public function store(PrinterRequest $request): JsonResponse
    {
        return ApiResponse::success(['printer' => ProductionResources::printer($this->service->createPrinter($request->validated()))], 201);
    }

    public function update(PrinterRequest $request, Printer $printer): JsonResponse
    {
        return ApiResponse::success(['printer' => ProductionResources::printer($this->service->updatePrinter($printer, $request->validated()))]);
    }

    public function test(Printer $printer): JsonResponse
    {
        return ApiResponse::success(['printJob' => ProductionResources::job($this->service->test($printer))], 201);
    }
}
