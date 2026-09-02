<?php

namespace App\Domain\Catalog\Http\Controllers;

use App\Domain\Catalog\Http\Requests\ChannelsRequest;
use App\Domain\Catalog\Http\Requests\ProductRequest;
use App\Domain\Catalog\Http\Requests\ReorderRequest;
use App\Domain\Catalog\Http\Resources\CatalogResources;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductCategory;
use App\Domain\Catalog\Services\ProductService;
use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $service) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(['products' => $this->service->list($request->query('categoryId'))->map(fn ($p) => CatalogResources::product($p))->all()]);
    }

    public function store(ProductRequest $request): JsonResponse
    {
        $category = ProductCategory::query()->findOrFail($request->validated('categoryId'));

        return ApiResponse::success(['product' => CatalogResources::product($this->service->create($category, $request->validated()), true)], 201);
    }

    public function show(Product $product): JsonResponse
    {
        return ApiResponse::success(['product' => CatalogResources::product($this->service->hydrate($product), true)]);
    }

    public function update(ProductRequest $request, Product $product): JsonResponse
    {
        return ApiResponse::success(['product' => CatalogResources::product($this->service->update($product, $request->validated()), true)]);
    }

    public function availability(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate(['isAvailable' => ['required', 'boolean']]);

        return ApiResponse::success(['product' => CatalogResources::product($this->service->setAvailability($product, $data['isAvailable']), true)]);
    }

    public function channels(ChannelsRequest $request, Product $product): JsonResponse
    {
        return ApiResponse::success(['product' => CatalogResources::product($this->service->updateChannels($product, $request->validated('channels')), true)]);
    }

    public function duplicate(Product $product): JsonResponse
    {
        return ApiResponse::success(['product' => CatalogResources::product($this->service->duplicate($product), true)], 201);
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->service->delete($product);

        return ApiResponse::success(['deletedId' => $product->id]);
    }

    public function reorder(ReorderRequest $request, ProductCategory $category): JsonResponse
    {
        return ApiResponse::success(['products' => $this->service->reorder($category, $request->validated('items'))->map(fn ($p) => CatalogResources::product($p))->all()]);
    }
}
