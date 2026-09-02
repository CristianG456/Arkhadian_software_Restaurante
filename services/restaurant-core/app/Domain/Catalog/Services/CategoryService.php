<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Models\ProductCategory;
use App\Support\Http\ApiException;
use App\Support\Text\LabelNormalizer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class CategoryService
{
    public function listWithProducts(): Collection
    {
        return ProductCategory::query()
            ->withCount('products')
            ->with([
                'products.variants',
                'products.primaryImage',
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function create(array $data): ProductCategory
    {
        $name = LabelNormalizer::display($data['name']);

        try {
            return DB::transaction(function () use ($name, $data): ProductCategory {
                DB::select('select pg_advisory_xact_lock(hashtext(?))', ['catalog-category-order']);

                return ProductCategory::query()->create([
                    'name' => $name,
                    'normalized_name' => LabelNormalizer::comparable($name),
                    'description' => $data['description'] ?? null,
                    'sort_order' => ((int) ProductCategory::query()->max('sort_order')) + 1,
                    'is_active' => true,
                ]);
            });
        } catch (QueryException $exception) {
            $this->throwDuplicate($exception);
        }
    }

    public function update(ProductCategory $category, array $data): ProductCategory
    {
        if (array_key_exists('name', $data)) {
            $category->name = LabelNormalizer::display($data['name']);
            $category->normalized_name = LabelNormalizer::comparable($category->name);
        }
        if (array_key_exists('description', $data)) {
            $category->description = $data['description'];
        }
        if (array_key_exists('isActive', $data)) {
            $category->is_active = $data['isActive'];
        }

        try {
            $category->save();
        } catch (QueryException $exception) {
            $this->throwDuplicate($exception);
        }

        return $category->refresh()->loadCount('products');
    }

    public function delete(ProductCategory $category): void
    {
        $count = $category->products()->count();
        if ($count > 0) {
            throw new ApiException(
                'CATEGORY_NOT_EMPTY',
                'No puedes eliminar '.$category->name.' porque contiene '.$count.' productos.',
                409,
            );
        }

        $category->delete();
    }

    public function reorder(array $items): Collection
    {
        DB::transaction(function () use ($items): void {
            DB::select('select pg_advisory_xact_lock(hashtext(?))', ['catalog-category-order']);
            $requested = collect($items)->pluck('id')->sort()->values()->all();
            $current = ProductCategory::query()->lockForUpdate()->pluck('id')->sort()->values()->all();
            if ($requested !== $current) {
                throw new ApiException(
                    'INVALID_CATEGORY_ORDER',
                    'El orden debe incluir todas las categorías una sola vez.',
                    422,
                );
            }

            foreach ($items as $item) {
                ProductCategory::query()->whereKey($item['id'])->update([
                    'sort_order' => $item['sortOrder'],
                ]);
            }
        });

        return $this->listWithProducts();
    }

    private function throwDuplicate(QueryException $exception): never
    {
        if ((string) $exception->getCode() === '23505') {
            throw new ApiException(
                'CATEGORY_ALREADY_EXISTS',
                'Ya existe una categoría con ese nombre.',
                409,
                $exception,
            );
        }

        throw $exception;
    }
}
