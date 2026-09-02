<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductVariant;
use App\Support\Http\ApiException;
use App\Support\Text\LabelNormalizer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class VariantService
{
    public function create(Product $product, array $data): ProductVariant
    {
        try {
            return DB::transaction(function () use ($product, $data): ProductVariant {
                $locked = Product::query()->lockForUpdate()->findOrFail($product->id);
                $name = LabelNormalizer::display($data['name']);
                $variant = $locked->variants()->create([
                    'name' => $name,
                    'normalized_name' => LabelNormalizer::comparable($name),
                    'price' => $data['price'],
                    'is_default' => false,
                    'is_active' => $data['isActive'] ?? true,
                    'is_available' => $data['isAvailable'] ?? true,
                    'sort_order' => ((int) $locked->variants()->max('sort_order')) + 1,
                ]);
                if (($data['isDefault'] ?? false) === true) {
                    $this->makeDefaultInsideTransaction($locked, $variant);
                }

                return $variant->refresh();
            });
        } catch (QueryException $exception) {
            $this->convertDuplicate($exception);
        }
    }

    public function update(Product $product, ProductVariant $variant, array $data): ProductVariant
    {
        $this->assertOwned($product, $variant);
        try {
            DB::transaction(function () use ($product, $variant, $data): void {
                Product::query()->lockForUpdate()->findOrFail($product->id);
                $locked = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);
                if (array_key_exists('name', $data)) {
                    $name = LabelNormalizer::display($data['name']);
                    $locked->name = $name;
                    $locked->normalized_name = LabelNormalizer::comparable($name);
                }
                foreach (['price' => 'price', 'isActive' => 'is_active', 'isAvailable' => 'is_available'] as $input => $column) {
                    if (array_key_exists($input, $data)) {
                        $locked->{$column} = $data[$input];
                    }
                }
                $locked->save();
                if (($data['isDefault'] ?? false) === true && ! $locked->is_default) {
                    $this->makeDefaultInsideTransaction($product, $locked);
                }
            });
        } catch (QueryException $exception) {
            $this->convertDuplicate($exception);
        }

        return $variant->refresh();
    }

    public function delete(Product $product, ProductVariant $variant): void
    {
        $this->assertOwned($product, $variant);
        DB::transaction(function () use ($product, $variant): void {
            Product::query()->lockForUpdate()->findOrFail($product->id);
            $variants = ProductVariant::query()->where('product_id', $product->id)->lockForUpdate()->get();
            if ($variants->count() <= 1) {
                throw new ApiException('LAST_VARIANT_REQUIRED', 'El producto debe conservar al menos una variante.', 409);
            }
            if ($variant->is_default) {
                throw new ApiException('DEFAULT_VARIANT_DELETE_BLOCKED', 'Elige otra variante predeterminada antes de eliminar esta.', 409);
            }
            ProductVariant::query()->findOrFail($variant->id)->delete();
        });
    }

    public function reorder(Product $product, array $items): Collection
    {
        DB::transaction(function () use ($product, $items): void {
            Product::query()->lockForUpdate()->findOrFail($product->id);
            $requested = collect($items)->pluck('id')->sort()->values()->all();
            $current = ProductVariant::query()->where('product_id', $product->id)->lockForUpdate()->pluck('id')->sort()->values()->all();
            if ($requested !== $current) {
                throw new ApiException('INVALID_VARIANT_ORDER', 'El orden debe incluir todas las variantes una sola vez.', 422);
            }
            foreach ($items as $item) {
                ProductVariant::query()->whereKey($item['id'])->update(['sort_order' => $item['sortOrder']]);
            }
        });

        return $product->variants()->get();
    }

    private function makeDefaultInsideTransaction(Product $product, ProductVariant $variant): void
    {
        ProductVariant::query()->where('product_id', $product->id)->where('id', '!=', $variant->id)->update(['is_default' => false]);
        $variant->update(['is_default' => true]);
    }

    private function assertOwned(Product $product, ProductVariant $variant): void
    {
        if ($variant->product_id !== $product->id) {
            throw new ApiException('VARIANT_NOT_FOUND', 'La variante no pertenece al producto.', 404);
        }
    }

    private function convertDuplicate(QueryException $exception): never
    {
        if ((string) $exception->getCode() === '23505') {
            throw new ApiException('VARIANT_ALREADY_EXISTS', 'Ya existe una variante con ese nombre.', 409, $exception);
        }
        throw $exception;
    }
}
