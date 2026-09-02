<?php

namespace App\Domain\Orders\Services;

use App\Domain\Catalog\Enums\ProductChannel;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductVariant;
use App\Domain\Orders\Support\DecimalMoney;
use App\Support\Http\ApiException;

final class OrderPricingService
{
    public function price(array $input, ProductChannel $channel): array
    {
        $product = Product::query()->with(['variants', 'channels', 'modifierAssignments.group.options'])->findOrFail($input['productId']);
        if (! $product->is_active || ! $product->is_available) {
            throw new ApiException('PRODUCT_NOT_AVAILABLE', $product->name.' ya no está disponible.', 409);
        }
        if (! $product->channels->firstWhere('channel', $channel)?->is_visible) {
            throw new ApiException('PRODUCT_NOT_AVAILABLE', $product->name.' no está disponible en este canal.', 409);
        }

        $variant = isset($input['variantId'])
            ? $product->variants->firstWhere('id', $input['variantId'])
            : $product->variants->firstWhere('is_default', true);
        if (! $variant instanceof ProductVariant || $variant->product_id !== $product->id || ! $variant->is_active || ! $variant->is_available) {
            throw new ApiException('VARIANT_NOT_AVAILABLE', 'La presentación seleccionada ya no está disponible.', 409);
        }
        if (! $product->allow_notes && filled($input['notes'] ?? null)) {
            throw new ApiException('PRODUCT_NOTES_NOT_ALLOWED', 'Este producto no permite observaciones.', 422);
        }

        $selectedIds = collect($input['modifierOptionIds'] ?? [])->unique()->values();
        if ($selectedIds->count() !== count($input['modifierOptionIds'] ?? [])) {
            throw new ApiException('INVALID_MODIFIER_SELECTION', 'No repitas opciones en el mismo producto.', 422);
        }
        $recognized = collect();
        $modifiers = [];
        $modifierMinor = 0;
        foreach ($product->modifierAssignments->where('is_active', true) as $assignment) {
            $group = $assignment->group;
            $available = $group->is_active ? $group->options->where('is_active', true) : collect();
            $chosen = $available->whereIn('id', $selectedIds);
            if ($chosen->count() < $assignment->min_select || $chosen->count() > $assignment->max_select) {
                throw new ApiException('INVALID_MODIFIER_SELECTION', $assignment->min_select > 0 ? 'Debes completar la selección de '.$group->name.'.' : 'Seleccionaste demasiadas opciones de '.$group->name.'.', 422);
            }
            foreach ($chosen as $option) {
                $recognized->push($option->id);
                $modifierMinor += DecimalMoney::toMinor((string) $option->price_delta);
                $modifiers[] = ['group' => $group, 'option' => $option];
            }
        }
        if ($recognized->unique()->sort()->values()->all() !== $selectedIds->sort()->values()->all()) {
            throw new ApiException('INVALID_MODIFIER_SELECTION', 'Una opción no pertenece a la configuración activa del producto.', 422);
        }

        $baseMinor = DecimalMoney::toMinor((string) $variant->price);
        $quantity = (int) $input['quantity'];

        return compact('product', 'variant', 'modifiers', 'baseMinor', 'modifierMinor', 'quantity') + [
            'unitTotalMinor' => $baseMinor + $modifierMinor,
            'lineTotalMinor' => ($baseMinor + $modifierMinor) * $quantity,
            'notes' => $input['notes'] ?? null,
        ];
    }
}
