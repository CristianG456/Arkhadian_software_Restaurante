<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Enums\ProductChannel;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductCategory;
use App\Domain\Catalog\Models\ProductChannelVisibility;
use App\Domain\Catalog\Models\ProductVariant;
use App\Support\Http\ApiException;
use App\Support\Text\LabelNormalizer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final class ProductService
{
    public function list(?string $categoryId = null): Collection
    {
        return Product::query()
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->with(['variants', 'primaryImage'])
            ->orderBy('category_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function hydrate(Product $product): Product
    {
        return $product->refresh()->load([
            'category',
            'variants',
            'channels',
            'images',
            'modifierAssignments.group.options',
            'productionCenter',
        ]);
    }

    public function create(ProductCategory $category, array $data): Product
    {
        $name = LabelNormalizer::display($data['name']);

        try {
            $product = DB::transaction(function () use ($category, $data, $name): Product {
                $lockedCategory = ProductCategory::query()->lockForUpdate()->findOrFail($category->id);
                $product = Product::query()->create([
                    'category_id' => $lockedCategory->id,
                    'name' => $name,
                    'normalized_name' => LabelNormalizer::comparable($name),
                    'description' => $data['description'] ?? null,
                    'is_active' => true,
                    'is_available' => $data['isAvailable'] ?? true,
                    'allow_notes' => $data['allowNotes'] ?? true,
                    'requires_production' => $data['requiresProduction'] ?? true,
                    'production_center_id' => $data['productionCenterId'] ?? null,
                    'sort_order' => ((int) $lockedCategory->products()->max('sort_order')) + 1,
                ]);

                ProductVariant::query()->create([
                    'product_id' => $product->id,
                    'name' => null,
                    'normalized_name' => null,
                    'price' => $data['price'],
                    'is_default' => true,
                    'is_active' => true,
                    'is_available' => $data['isAvailable'] ?? true,
                    'sort_order' => 1,
                ]);

                foreach (ProductChannel::cases() as $channel) {
                    ProductChannelVisibility::query()->create([
                        'product_id' => $product->id,
                        'channel' => $channel,
                        'is_visible' => true,
                    ]);
                }

                return $product;
            });
        } catch (QueryException $exception) {
            $this->throwDuplicate($exception, $category, $name);
        }

        return $this->hydrate($product);
    }

    public function update(Product $product, array $data): Product
    {
        try {
            DB::transaction(function () use ($product, $data): void {
                $locked = Product::query()->lockForUpdate()->findOrFail($product->id);
                $targetCategory = array_key_exists('categoryId', $data)
                    ? ProductCategory::query()->lockForUpdate()->findOrFail($data['categoryId'])
                    : $locked->category;

                if (array_key_exists('name', $data)) {
                    $locked->name = LabelNormalizer::display($data['name']);
                    $locked->normalized_name = LabelNormalizer::comparable($locked->name);
                }
                if ($targetCategory->id !== $locked->category_id) {
                    $locked->category_id = $targetCategory->id;
                    $locked->sort_order = ((int) $targetCategory->products()->max('sort_order')) + 1;
                }
                foreach ([
                    'description' => 'description',
                    'isActive' => 'is_active',
                    'isAvailable' => 'is_available',
                    'allowNotes' => 'allow_notes',
                    'requiresProduction' => 'requires_production',
                    'productionCenterId' => 'production_center_id',
                ] as $input => $column) {
                    if (array_key_exists($input, $data)) {
                        $locked->{$column} = $data[$input];
                    }
                }
                $locked->save();

                if (array_key_exists('price', $data)) {
                    $default = $locked->defaultVariant()->lockForUpdate()->first();
                    if ($default === null) {
                        throw new ApiException(
                            'PRODUCT_DEFAULT_VARIANT_REQUIRED',
                            'El producto debe conservar una variante predeterminada.',
                            409,
                        );
                    }
                    $default->update(['price' => $data['price']]);
                }
            });
        } catch (QueryException $exception) {
            $category = ProductCategory::query()->findOrFail($data['categoryId'] ?? $product->category_id);
            $this->throwDuplicate($exception, $category, $data['name'] ?? $product->name);
        }

        return $this->hydrate($product);
    }

    public function setAvailability(Product $product, bool $available): Product
    {
        $product->update(['is_available' => $available]);

        return $this->hydrate($product);
    }

    public function updateChannels(Product $product, array $channels): Product
    {
        DB::transaction(function () use ($product, $channels): void {
            Product::query()->lockForUpdate()->findOrFail($product->id);
            foreach (ProductChannel::cases() as $channel) {
                ProductChannelVisibility::query()->updateOrCreate(
                    ['product_id' => $product->id, 'channel' => $channel->value],
                    ['is_visible' => (bool) $channels[$channel->value]],
                );
            }
        });

        return $this->hydrate($product);
    }

    public function reorder(ProductCategory $category, array $items): Collection
    {
        DB::transaction(function () use ($category, $items): void {
            ProductCategory::query()->lockForUpdate()->findOrFail($category->id);
            $requested = collect($items)->pluck('id')->sort()->values()->all();
            $current = Product::query()
                ->where('category_id', $category->id)
                ->lockForUpdate()
                ->pluck('id')
                ->sort()
                ->values()
                ->all();
            if ($requested !== $current) {
                throw new ApiException(
                    'INVALID_PRODUCT_ORDER',
                    'El orden debe incluir todos los productos de la categoría una sola vez.',
                    422,
                );
            }

            foreach ($items as $item) {
                Product::query()->whereKey($item['id'])->update([
                    'sort_order' => $item['sortOrder'],
                ]);
            }
        });

        return $this->list($category->id);
    }

    public function duplicate(Product $product): Product
    {
        $copiedPaths = [];

        try {
            $copy = DB::transaction(function () use ($product, &$copiedPaths): Product {
                $source = Product::query()
                    ->with(['variants', 'channels', 'modifierAssignments', 'images'])
                    ->lockForUpdate()
                    ->findOrFail($product->id);
                $category = ProductCategory::query()->lockForUpdate()->findOrFail($source->category_id);
                $name = $this->availableCopyName($source);

                $copy = Product::query()->create([
                    'category_id' => $source->category_id,
                    'name' => $name,
                    'normalized_name' => LabelNormalizer::comparable($name),
                    'description' => $source->description,
                    'is_active' => $source->is_active,
                    'is_available' => $source->is_available,
                    'allow_notes' => $source->allow_notes,
                    'requires_production' => $source->requires_production,
                    'production_center_id' => $source->production_center_id,
                    'sort_order' => ((int) $category->products()->max('sort_order')) + 1,
                ]);

                foreach ($source->variants as $variant) {
                    $copy->variants()->create($variant->only([
                        'name',
                        'normalized_name',
                        'price',
                        'is_default',
                        'is_active',
                        'is_available',
                        'sort_order',
                    ]));
                }
                foreach ($source->channels as $channel) {
                    $copy->channels()->create([
                        'channel' => $channel->channel,
                        'is_visible' => $channel->is_visible,
                    ]);
                }
                foreach ($source->modifierAssignments as $assignment) {
                    $copy->modifierAssignments()->create($assignment->only([
                        'modifier_group_id',
                        'min_select',
                        'max_select',
                        'sort_order',
                        'is_active',
                    ]));
                }
                foreach ($source->images as $image) {
                    $extension = pathinfo($image->storage_path, PATHINFO_EXTENSION);
                    $path = 'catalog/products/'.$copy->id.'/'.Str::ulid().'.'.$extension;
                    if (! Storage::disk('local')->copy($image->storage_path, $path)) {
                        throw new ApiException('IMAGE_COPY_FAILED', 'No pudimos copiar la imagen del producto.', 500);
                    }
                    $copiedPaths[] = $path;
                    $copy->images()->create([
                        'storage_path' => $path,
                        'mime_type' => $image->mime_type,
                        'file_size' => $image->file_size,
                        'width' => $image->width,
                        'height' => $image->height,
                        'is_primary' => $image->is_primary,
                        'sort_order' => $image->sort_order,
                    ]);
                }

                return $copy;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($copiedPaths);
            throw $exception;
        }

        return $this->hydrate($copy);
    }

    public function delete(Product $product): void
    {
        $paths = $product->images()->pluck('storage_path')->all();

        DB::transaction(function () use ($product, $paths): void {
            Product::query()->lockForUpdate()->findOrFail($product->id)->delete();
            DB::afterCommit(static fn () => Storage::disk('local')->delete($paths));
        });
    }

    private function availableCopyName(Product $source): string
    {
        $base = 'Copia de '.$source->name;
        $name = $base;
        $suffix = 2;

        while (Product::query()
            ->where('category_id', $source->category_id)
            ->where('normalized_name', LabelNormalizer::comparable($name))
            ->exists()) {
            $name = $base.' '.$suffix;
            $suffix++;
        }

        return $name;
    }

    private function throwDuplicate(
        QueryException $exception,
        ProductCategory $category,
        string $name,
    ): never {
        if ((string) $exception->getCode() === '23505') {
            throw new ApiException(
                'PRODUCT_ALREADY_EXISTS',
                'Ya existe '.$name.' en '.$category->name.'.',
                409,
                $exception,
            );
        }

        throw $exception;
    }
}
