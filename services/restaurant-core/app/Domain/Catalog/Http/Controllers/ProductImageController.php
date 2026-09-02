<?php

namespace App\Domain\Catalog\Http\Controllers;

use App\Domain\Catalog\Http\Requests\ImageRequest;
use App\Domain\Catalog\Http\Resources\CatalogResources;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductImage;
use App\Domain\Catalog\Services\ProductImageService;
use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductImageController extends Controller
{
    public function __construct(private readonly ProductImageService $service) {}

    public function store(ImageRequest $request, Product $product): JsonResponse
    {
        return ApiResponse::success(['image' => CatalogResources::image($this->service->store($product, $request->file('image')))], 201);
    }

    public function show(ProductImage $image): StreamedResponse
    {
        return Storage::disk('local')->response($image->storage_path, null, ['Content-Type' => $image->mime_type, 'Cache-Control' => 'private, max-age=3600', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function destroy(Product $product, ProductImage $image): JsonResponse
    {
        abort_unless($image->product_id === $product->id, 404);
        $this->service->delete($image);

        return ApiResponse::success(['deletedId' => $image->id]);
    }
}
