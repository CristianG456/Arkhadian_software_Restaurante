<?php

namespace App\Domain\Catalog\Http\Resources;

use App\Domain\Catalog\Models\ModifierGroup;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductCategory;
use App\Domain\Catalog\Models\ProductImage;
use App\Domain\Catalog\Models\ProductVariant;

final class CatalogResources
{
    public static function variant(ProductVariant $variant): array
    {
        return ['id' => $variant->id, 'name' => $variant->name, 'price' => (string) $variant->price, 'isDefault' => $variant->is_default, 'isActive' => $variant->is_active, 'isAvailable' => $variant->is_available, 'sortOrder' => $variant->sort_order];
    }

    public static function image(ProductImage $image): array
    {
        return ['id' => $image->id, 'mimeType' => $image->mime_type, 'fileSize' => $image->file_size, 'width' => $image->width, 'height' => $image->height, 'isPrimary' => $image->is_primary, 'url' => '/api/catalog/images/'.$image->id];
    }

    public static function product(Product $product, bool $detail = false): array
    {
        $variants = $product->relationLoaded('variants') ? $product->variants->map(fn ($v) => self::variant($v))->values()->all() : [];
        $result = ['id' => $product->id, 'categoryId' => $product->category_id, 'name' => $product->name, 'description' => $product->description, 'isActive' => $product->is_active, 'isAvailable' => $product->is_available, 'allowNotes' => $product->allow_notes, 'requiresProduction' => $product->requires_production, 'productionCenterId' => $product->production_center_id, 'sortOrder' => $product->sort_order, 'variantsCount' => count($variants), 'variants' => $variants, 'basePrice' => collect($variants)->firstWhere('isDefault', true)['price'] ?? null, 'primaryImage' => $product->relationLoaded('primaryImage') && $product->primaryImage ? self::image($product->primaryImage) : null];
        if ($detail) {
            $result['category'] = $product->relationLoaded('category') ? ['id' => $product->category->id, 'name' => $product->category->name] : null;
            $result['channels'] = $product->channels->mapWithKeys(fn ($c) => [$c->channel->value => $c->is_visible])->all();
            $result['images'] = $product->images->map(fn ($i) => self::image($i))->values()->all();
            $result['modifierAssignments'] = $product->modifierAssignments->map(fn ($a) => ['id' => $a->id, 'modifierGroupId' => $a->modifier_group_id, 'minSelect' => $a->min_select, 'maxSelect' => $a->max_select, 'sortOrder' => $a->sort_order, 'isActive' => $a->is_active, 'group' => self::group($a->group)])->values()->all();
            $result['productionCenter'] = $product->productionCenter ? ['id' => $product->productionCenter->id, 'name' => $product->productionCenter->name] : null;
        }

        return $result;
    }

    public static function category(ProductCategory $category): array
    {
        return ['id' => $category->id, 'name' => $category->name, 'description' => $category->description, 'sortOrder' => $category->sort_order, 'isActive' => $category->is_active, 'productsCount' => (int) ($category->products_count ?? $category->products->count()), 'products' => $category->relationLoaded('products') ? $category->products->map(fn ($p) => self::product($p))->values()->all() : []];
    }

    public static function group(ModifierGroup $group): array
    {
        return ['id' => $group->id, 'name' => $group->name, 'isActive' => $group->is_active, 'assignmentsCount' => (int) ($group->assignments_count ?? 0), 'options' => $group->relationLoaded('options') ? $group->options->map(fn ($o) => ['id' => $o->id, 'name' => $o->name, 'priceDelta' => (string) $o->price_delta, 'isActive' => $o->is_active, 'sortOrder' => $o->sort_order])->values()->all() : []];
    }
}
