<?php

namespace App\Domain\Catalog\Http\Controllers;

use App\Domain\Catalog\Http\Requests\CategoryRequest;
use App\Domain\Catalog\Http\Requests\ReorderRequest;
use App\Domain\Catalog\Http\Resources\CatalogResources;
use App\Domain\Catalog\Models\ProductCategory;
use App\Domain\Catalog\Services\CategoryService;
use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $service) {}

    public function index(): JsonResponse
    {
        return ApiResponse::success(['categories' => $this->service->listWithProducts()->map(fn ($c) => CatalogResources::category($c))->all()]);
    }

    public function store(CategoryRequest $request): JsonResponse
    {
        return ApiResponse::success(['category' => CatalogResources::category($this->service->create($request->validated())->loadCount('products'))], 201);
    }

    public function update(CategoryRequest $request, ProductCategory $category): JsonResponse
    {
        return ApiResponse::success(['category' => CatalogResources::category($this->service->update($category, $request->validated()))]);
    }

    public function destroy(ProductCategory $category): JsonResponse
    {
        $this->service->delete($category);

        return ApiResponse::success(['deletedId' => $category->id]);
    }

    public function reorder(ReorderRequest $request): JsonResponse
    {
        return ApiResponse::success(['categories' => $this->service->reorder($request->validated('items'))->map(fn ($c) => CatalogResources::category($c))->all()]);
    }
}
