<?php

namespace App\Domain\Areas\Http\Controllers;

use App\Domain\Areas\Http\Requests\ReorderAreasRequest;
use App\Domain\Areas\Http\Requests\StoreAreaRequest;
use App\Domain\Areas\Http\Requests\UpdateAreaRequest;
use App\Domain\Areas\Http\Resources\AreaResource;
use App\Domain\Areas\Models\Area;
use App\Domain\Areas\Services\AreaService;
use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function __construct(private readonly AreaService $service) {}

    public function index(Request $request): JsonResponse
    {
        $areas = AreaResource::collection($this->service->listWithTables())->resolve($request);

        return ApiResponse::success(['areas' => $areas]);
    }

    public function store(StoreAreaRequest $request): JsonResponse
    {
        $area = $this->service->create($request->validated('name'));

        return ApiResponse::success(['area' => (new AreaResource($area))->resolve($request)], 201);
    }

    public function show(Request $request, Area $area): JsonResponse
    {
        $area = $this->service->hydrate($area);

        return ApiResponse::success(['area' => (new AreaResource($area))->resolve($request)]);
    }

    public function update(UpdateAreaRequest $request, Area $area): JsonResponse
    {
        $area = $this->service->update($area, $request->validated());

        return ApiResponse::success(['area' => (new AreaResource($area))->resolve($request)]);
    }

    public function destroy(Area $area): JsonResponse
    {
        $this->service->delete($area);

        return ApiResponse::success(['deletedId' => $area->id]);
    }

    public function reorder(ReorderAreasRequest $request): JsonResponse
    {
        $areas = $this->service->reorder($request->validated('items'));

        return ApiResponse::success([
            'areas' => AreaResource::collection($areas)->resolve($request),
        ]);
    }
}
