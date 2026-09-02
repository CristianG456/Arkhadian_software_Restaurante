<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Models\ModifierGroup;
use App\Domain\Catalog\Models\ModifierOption;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductModifierGroup;
use App\Support\Http\ApiException;
use App\Support\Text\LabelNormalizer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class ModifierService
{
    public function list(): Collection
    {
        return ModifierGroup::query()->with('options')->withCount('assignments')->orderBy('name')->get();
    }

    public function createGroup(array $data): ModifierGroup
    {
        try {
            $name = LabelNormalizer::display($data['name']);

            return ModifierGroup::query()->create(['name' => $name, 'normalized_name' => LabelNormalizer::comparable($name), 'is_active' => $data['isActive'] ?? true]);
        } catch (QueryException $e) {
            $this->duplicate($e, 'MODIFIER_GROUP_ALREADY_EXISTS', 'Ya existe un grupo con ese nombre.');
        }
    }

    public function updateGroup(ModifierGroup $group, array $data): ModifierGroup
    {
        if (isset($data['name'])) {
            $group->name = LabelNormalizer::display($data['name']);
            $group->normalized_name = LabelNormalizer::comparable($group->name);
        }
        if (array_key_exists('isActive', $data)) {
            $group->is_active = $data['isActive'];
        }
        try {
            $group->save();
        } catch (QueryException $e) {
            $this->duplicate($e, 'MODIFIER_GROUP_ALREADY_EXISTS', 'Ya existe un grupo con ese nombre.');
        }

        return $group->refresh()->load('options')->loadCount('assignments');
    }

    public function deleteGroup(ModifierGroup $group): void
    {
        DB::transaction(function () use ($group): void {
            $locked = ModifierGroup::query()->lockForUpdate()->findOrFail($group->id);
            $productsCount = $locked->assignments()->count();
            if ($productsCount > 0) {
                throw new ApiException(
                    'MODIFIER_GROUP_IN_USE',
                    'No puedes eliminar este grupo porque está siendo utilizado por '.$productsCount.' '.($productsCount === 1 ? 'producto.' : 'productos.'),
                    409,
                );
            }

            $locked->delete();
        });
    }

    public function createOption(ModifierGroup $group, array $data): ModifierOption
    {
        try {
            return DB::transaction(function () use ($group, $data): ModifierOption {
                $locked = ModifierGroup::query()->lockForUpdate()->findOrFail($group->id);
                $name = LabelNormalizer::display($data['name']);

                return $locked->options()->create(['name' => $name, 'normalized_name' => LabelNormalizer::comparable($name), 'price_delta' => $data['priceDelta'] ?? '0', 'sort_order' => ((int) $locked->options()->max('sort_order')) + 1, 'is_active' => $data['isActive'] ?? true]);
            });
        } catch (QueryException $e) {
            $this->duplicate($e, 'MODIFIER_OPTION_ALREADY_EXISTS', 'Ya existe una opción con ese nombre en el grupo.');
        }
    }

    public function updateOption(ModifierGroup $group, ModifierOption $option, array $data): ModifierOption
    {
        $this->assertOption($group, $option);
        if (isset($data['name'])) {
            $option->name = LabelNormalizer::display($data['name']);
            $option->normalized_name = LabelNormalizer::comparable($option->name);
        }
        if (array_key_exists('priceDelta', $data)) {
            $option->price_delta = $data['priceDelta'];
        }
        if (array_key_exists('isActive', $data)) {
            if (! $data['isActive']) {
                $remaining = $group->options()->where('is_active', true)->whereKeyNot($option->id)->count();
                if ($group->assignments()->where('is_active', true)->where('min_select', '>', $remaining)->exists()) {
                    throw new ApiException('MODIFIER_MINIMUM_BROKEN', 'La opción es necesaria para cumplir el mínimo configurado en un producto.', 409);
                }
            }
            $option->is_active = $data['isActive'];
        }
        try {
            $option->save();
        } catch (QueryException $e) {
            $this->duplicate($e, 'MODIFIER_OPTION_ALREADY_EXISTS', 'Ya existe una opción con ese nombre en el grupo.');
        }

        return $option->refresh();
    }

    public function deleteOption(ModifierGroup $group, ModifierOption $option): void
    {
        $this->assertOption($group, $option);
        $remaining = $group->options()->where('is_active', true)->whereKeyNot($option->id)->count();
        if ($group->assignments()->where('is_active', true)->where('min_select', '>', $remaining)->exists()) {
            throw new ApiException('MODIFIER_OPTION_REQUIRED_BY_CONFIGURATION', 'No puedes eliminar esta opción porque es necesaria para cumplir el mínimo configurado en un producto.', 409);
        }
        $option->delete();
    }

    public function assign(Product $product, array $data): ProductModifierGroup
    {
        try {
            return DB::transaction(function () use ($product, $data): ProductModifierGroup {
                $group = ModifierGroup::query()->withCount(['options as active_options_count' => fn ($q) => $q->where('is_active', true)])->findOrFail($data['modifierGroupId']);
                if (! $group->is_active) {
                    throw new ApiException('MODIFIER_GROUP_INACTIVE', 'Reactiva el grupo antes de asignarlo a un producto.', 409);
                }
                if ($data['maxSelect'] > $group->active_options_count) {
                    throw new ApiException('MODIFIER_MAX_EXCEEDS_OPTIONS', 'El máximo no puede superar las opciones activas.', 422);
                }

                return $product->modifierAssignments()->create(['modifier_group_id' => $group->id, 'min_select' => $data['minSelect'], 'max_select' => $data['maxSelect'], 'sort_order' => ((int) $product->modifierAssignments()->max('sort_order')) + 1, 'is_active' => $data['isActive'] ?? true]);
            });
        } catch (QueryException $e) {
            $this->duplicate($e, 'MODIFIER_GROUP_ALREADY_ASSIGNED', 'Este grupo ya está asignado al producto.');
        }
    }

    public function unassign(Product $product, ProductModifierGroup $assignment): void
    {
        if ($assignment->product_id !== $product->id) {
            throw new ApiException('ASSIGNMENT_NOT_FOUND', 'La asignación no pertenece al producto.', 404);
        }
        $assignment->delete();
    }

    private function assertOption(ModifierGroup $group, ModifierOption $option): void
    {
        if ($option->modifier_group_id !== $group->id) {
            throw new ApiException('MODIFIER_OPTION_NOT_FOUND', 'La opción no pertenece al grupo.', 404);
        }
    }

    private function duplicate(QueryException $e, string $code, string $message): never
    {
        if ((string) $e->getCode() === '23505') {
            throw new ApiException($code, $message, 409, $e);
        } throw $e;
    }
}
