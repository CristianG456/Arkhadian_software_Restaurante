<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductImage;
use App\Support\Http\ApiException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class ProductImageService
{
    private const TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    public function store(Product $product, UploadedFile $file): ProductImage
    {
        $mime = $file->getMimeType();
        if (! isset(self::TYPES[$mime])) {
            throw new ApiException('INVALID_IMAGE_TYPE', 'La imagen debe ser JPG, PNG o WEBP.', 422);
        }
        $dimensions = @getimagesize($file->getRealPath());
        if ($dimensions === false || $dimensions[0] < 1 || $dimensions[1] < 1 || $dimensions[0] > 6000 || $dimensions[1] > 6000) {
            throw new ApiException('INVALID_IMAGE_CONTENT', 'El archivo no contiene una imagen válida o sus dimensiones son demasiado grandes.', 422);
        }
        $path = 'catalog/products/'.$product->id.'/'.Str::ulid().'.'.self::TYPES[$mime];
        $stream = fopen($file->getRealPath(), 'rb');
        if ($stream === false || ! Storage::disk('local')->put($path, $stream)) {
            throw new ApiException('IMAGE_STORE_FAILED', 'No pudimos guardar la imagen.', 500);
        }
        if (is_resource($stream)) {
            fclose($stream);
        }
        try {
            return DB::transaction(function () use ($product, $file, $mime, $dimensions, $path): ProductImage {
                Product::query()->lockForUpdate()->findOrFail($product->id);
                $product->images()->update(['is_primary' => false]);

                return $product->images()->create(['storage_path' => $path, 'mime_type' => $mime, 'file_size' => $file->getSize(), 'width' => $dimensions[0], 'height' => $dimensions[1], 'is_primary' => true, 'sort_order' => ((int) $product->images()->max('sort_order')) + 1]);
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }
    }

    public function delete(ProductImage $image): void
    {
        $path = $image->storage_path;
        DB::transaction(function () use ($image, $path): void {
            $productId = $image->product_id;
            $wasPrimary = $image->is_primary;
            $image->delete();
            if ($wasPrimary) {
                ProductImage::query()->where('product_id', $productId)->orderBy('sort_order')->first()?->update(['is_primary' => true]);
            } DB::afterCommit(fn () => Storage::disk('local')->delete($path));
        });
    }
}
