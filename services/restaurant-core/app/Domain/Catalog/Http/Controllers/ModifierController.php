<?php

namespace App\Domain\Catalog\Http\Controllers;

use App\Domain\Catalog\Http\Requests\AssignmentRequest;
use App\Domain\Catalog\Http\Requests\ModifierRequest;
use App\Domain\Catalog\Http\Resources\CatalogResources;
use App\Domain\Catalog\Models\ModifierGroup;
use App\Domain\Catalog\Models\ModifierOption;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductModifierGroup;
use App\Domain\Catalog\Services\ModifierService;
use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

class ModifierController extends Controller
{
    public function __construct(private readonly ModifierService $service) {}

    public function index(): JsonResponse
    {
        return ApiResponse::success(['modifierGroups' => $this->service->list()->map(fn ($g) => CatalogResources::group($g))->all()]);
    }

    public function storeGroup(ModifierRequest $request): JsonResponse
    {
        return ApiResponse::success(['modifierGroup' => CatalogResources::group($this->service->createGroup($request->validated())->load('options')->loadCount('assignments'))], 201);
    }

    public function updateGroup(ModifierRequest $request, ModifierGroup $group): JsonResponse
    {
        return ApiResponse::success(['modifierGroup' => CatalogResources::group($this->service->updateGroup($group, $request->validated()))]);
    }

    public function destroyGroup(ModifierGroup $group): JsonResponse
    {
        $this->service->deleteGroup($group);

        return ApiResponse::success(['deletedId' => $group->id]);
    }

    public function storeOption(ModifierRequest $request, ModifierGroup $group): JsonResponse
    {
        $option = $this->service->createOption($group, $request->validated());

        return ApiResponse::success(['option' => ['id' => $option->id, 'name' => $option->name, 'priceDelta' => (string) $option->price_delta, 'isActive' => $option->is_active, 'sortOrder' => $option->sort_order]], 201);
    }

    public function updateOption(ModifierRequest $request, ModifierGroup $group, ModifierOption $option): JsonResponse
    {
        $option = $this->service->updateOption($group, $option, $request->validated());

        return ApiResponse::success(['option' => ['id' => $option->id, 'name' => $option->name, 'priceDelta' => (string) $option->price_delta, 'isActive' => $option->is_active, 'sortOrder' => $option->sort_order]]);
    }

    public function destroyOption(ModifierGroup $group, ModifierOption $option): JsonResponse
    {
        $this->service->deleteOption($group, $option);

        return ApiResponse::success(['deletedId' => $option->id]);
    }

    public function assign(AssignmentRequest $request, Product $product): JsonResponse
    {
        $assignment = $this->service->assign($product, $request->validated())->load('group.options');

        return ApiResponse::success(['assignment' => ['id' => $assignment->id, 'modifierGroupId' => $assignment->modifier_group_id, 'minSelect' => $assignment->min_select, 'maxSelect' => $assignment->max_select, 'isActive' => $assignment->is_active, 'group' => CatalogResources::group($assignment->group)]], 201);
    }

    public function unassign(Product $product, ProductModifierGroup $assignment): JsonResponse
    {
        $this->service->unassign($product, $assignment);

        return ApiResponse::success(['deletedId' => $assignment->id]);
    }
}
