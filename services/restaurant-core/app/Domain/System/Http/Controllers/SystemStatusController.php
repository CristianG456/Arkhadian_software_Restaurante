<?php

namespace App\Domain\System\Http\Controllers;

use App\Domain\System\Services\SystemStatusService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

final class SystemStatusController
{
    public function __construct(private readonly SystemStatusService $status) {}

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success($this->status->snapshot());
    }
}
