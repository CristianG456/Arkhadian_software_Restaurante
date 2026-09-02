<?php

namespace App\Domain\Catalog\Http\Controllers;

use App\Domain\Catalog\Http\Requests\ReorderRequest;
use App\Domain\Catalog\Http\Requests\VariantRequest;
use App\Domain\Catalog\Http\Resources\CatalogResources;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductVariant;
use App\Domain\Catalog\Services\VariantService;
use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

class VariantController extends Controller
{
    public function __construct(private readonly VariantService $service) {}

    public function store(VariantRequest $request, Product $product): JsonResponse
    {
        return ApiResponse::success(['variant' => CatalogResources::variant($this->service->create($product, $request->validated()))], 201);
    }

    public function update(VariantRequest $request, Product $product, ProductVariant $variant): JsonResponse
    {
        return ApiResponse::success(['variant' => CatalogResources::variant($this->service->update($product, $variant, $request->validated()))]);
    }

    public function destroy(Product $product, ProductVariant $variant): JsonResponse
    {
        $this->service->delete($product, $variant);

        return ApiResponse::success(['deletedId' => $variant->id]);
    }

    public function reorder(ReorderRequest $request, Product $product): JsonResponse
    {
        return ApiResponse::success(['variants' => $this->service->reorder($product, $request->validated('items'))->map(fn ($v) => CatalogResources::variant($v))->all()]);
    }
}
