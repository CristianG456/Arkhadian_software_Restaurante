<?php

namespace App\Domain\Tables\Http\Controllers;

use App\Domain\Areas\Models\Area;
use App\Domain\Tables\Http\Requests\BulkStoreRestaurantTablesRequest;
use App\Domain\Tables\Http\Requests\MoveRestaurantTableRequest;
use App\Domain\Tables\Http\Requests\ReorderRestaurantTablesRequest;
use App\Domain\Tables\Http\Requests\StoreRestaurantTableRequest;
use App\Domain\Tables\Http\Requests\UpdateRestaurantTableRequest;
use App\Domain\Tables\Http\Resources\RestaurantTableResource;
use App\Domain\Tables\Models\RestaurantTable;
use App\Domain\Tables\Services\RestaurantTableService;
use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RestaurantTableController extends Controller
{
    public function __construct(private readonly RestaurantTableService $service) {}

    public function index(Request $request, Area $area): JsonResponse
    {
        $tables = RestaurantTableResource::collection($this->service->listForArea($area))->resolve($request);

        return ApiResponse::success(['tables' => $tables]);
    }

    public function store(StoreRestaurantTableRequest $request, Area $area): JsonResponse
    {
        $table = $this->service->create($area, $request->validated('label'));

        return ApiResponse::success([
            'table' => (new RestaurantTableResource($table))->resolve($request),
        ], 201);
    }

    public function show(Request $request, RestaurantTable $table): JsonResponse
    {
        return ApiResponse::success([
            'table' => (new RestaurantTableResource($table))->resolve($request),
        ]);
    }

    public function update(UpdateRestaurantTableRequest $request, RestaurantTable $table): JsonResponse
    {
        $table = $this->service->update($table, $request->validated());

        return ApiResponse::success([
            'table' => (new RestaurantTableResource($table))->resolve($request),
        ]);
    }

    public function move(MoveRestaurantTableRequest $request, RestaurantTable $table): JsonResponse
    {
        $targetArea = Area::query()->findOrFail($request->validated('areaId'));
        $table = $this->service->move($table, $targetArea);

        return ApiResponse::success([
            'table' => (new RestaurantTableResource($table))->resolve($request),
        ]);
    }

    public function destroy(RestaurantTable $table): JsonResponse
    {
        $this->service->delete($table);

        return ApiResponse::success(['deletedId' => $table->id]);
    }

    public function bulkStore(BulkStoreRestaurantTablesRequest $request, Area $area): JsonResponse
    {
        $result = $this->service->bulkCreate(
            $area,
            $request->integer('from'),
            $request->integer('to'),
        );

        return ApiResponse::success([
            'created' => RestaurantTableResource::collection($result['created'])->resolve($request),
            'skippedExisting' => RestaurantTableResource::collection($result['skippedExisting'])->resolve($request),
            'createdCount' => $result['created']->count(),
            'skippedCount' => $result['skippedExisting']->count(),
        ], 201);
    }

    public function reorder(ReorderRestaurantTablesRequest $request, Area $area): JsonResponse
    {
        $tables = $this->service->reorder($area, $request->validated('items'));

        return ApiResponse::success([
            'tables' => RestaurantTableResource::collection($tables)->resolve($request),
        ]);
    }
}
