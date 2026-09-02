<?php

namespace App\Domain\TableQr\Http\Controllers;

use App\Domain\TableQr\Http\Resources\TableQrCredentialResource;
use App\Domain\TableQr\Models\TableQrCredential;
use App\Domain\TableQr\Services\TableQrCredentialService;
use App\Domain\Tables\Models\RestaurantTable;
use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TableQrCredentialController extends Controller
{
    public function __construct(private readonly TableQrCredentialService $service) {}

    public function show(Request $request, RestaurantTable $table): JsonResponse
    {
        return $this->respond($request, $this->service->current($table));
    }

    public function store(Request $request, RestaurantTable $table): JsonResponse
    {
        return $this->respond($request, $this->service->generate($table), 201);
    }

    public function regenerate(Request $request, RestaurantTable $table): JsonResponse
    {
        return $this->respond($request, $this->service->regenerate($table));
    }

    public function revoke(Request $request, RestaurantTable $table): JsonResponse
    {
        return $this->respond($request, $this->service->revoke($table));
    }

    private function respond(
        Request $request,
        TableQrCredential $credential,
        int $status = 200,
    ): JsonResponse {
        return ApiResponse::success([
            'credential' => (new TableQrCredentialResource($credential))->resolve($request),
        ], $status);
    }
}
