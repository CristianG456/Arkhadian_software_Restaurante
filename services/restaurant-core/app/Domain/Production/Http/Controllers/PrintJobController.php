<?php

namespace App\Domain\Production\Http\Controllers;

use App\Domain\Production\Http\Resources\ProductionResources;
use App\Domain\Production\Models\PrintJob;
use App\Domain\Production\Services\PrintJobProcessor;
use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

class PrintJobController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiResponse::success(['printJobs' => PrintJob::query()->with(['printer', 'center'])->latest()->limit(100)->get()->map(fn ($job) => ProductionResources::job($job))->all()]);
    }

    public function retry(PrintJob $printJob, PrintJobProcessor $processor): JsonResponse
    {
        return ApiResponse::success(['printJob' => ProductionResources::job($processor->retry($printJob)->load(['printer', 'center']))]);
    }

    public function process(PrintJobProcessor $processor): JsonResponse
    {
        $job = $processor->processNext();

        return ApiResponse::success(['printJob' => $job ? ProductionResources::job($job->load(['printer', 'center'])) : null]);
    }
}
