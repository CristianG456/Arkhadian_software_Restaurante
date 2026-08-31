<?php

namespace App\Domain\Tables\Services;

use App\Domain\Areas\Models\Area;
use App\Domain\Tables\Models\RestaurantTable;
use App\Support\Http\ApiException;
use App\Support\Text\LabelNormalizer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class RestaurantTableService
{
    public function listForArea(Area $area): Collection
    {
        return $area->tables()->get();
    }

    public function create(Area $area, string $label): RestaurantTable
    {
        $label = LabelNormalizer::tableDisplay($label);
        try {
            return DB::transaction(fn (): RestaurantTable => RestaurantTable::query()->create([
                'area_id' => $area->id,
                'label' => $label,
                'normalized_label' => LabelNormalizer::comparable($label),
                'sort_order' => ((int) $area->tables()->max('sort_order')) + 1,
                'is_active' => true,
            ]));
        } catch (QueryException $exception) {
            $this->throwDuplicate($exception, $area, $label);
        }
    }

    public function update(RestaurantTable $table, array $data): RestaurantTable
    {
        $targetArea = array_key_exists('areaId', $data)
            ? Area::query()->findOrFail($data['areaId'])
            : $table->area;

        if (array_key_exists('label', $data)) {
            $table->label = LabelNormalizer::tableDisplay($data['label']);
            $table->normalized_label = LabelNormalizer::comparable($table->label);
        }
        if ($targetArea->id !== $table->area_id) {
            $table->area_id = $targetArea->id;
            $table->sort_order = ((int) $targetArea->tables()->max('sort_order')) + 1;
        }
        if (array_key_exists('isActive', $data)) {
            $table->is_active = $data['isActive'];
        }

        try {
            $table->save();
        } catch (QueryException $exception) {
            $this->throwDuplicate($exception, $targetArea, $table->label);
        }

        return $table->refresh();
    }

    public function bulkCreate(Area $area, int $from, int $to): array
    {
        if (($to - $from) + 1 > 100) {
            throw new ApiException('TABLE_BULK_LIMIT', 'Puedes crear máximo 100 mesas por operación.', 422);
        }

        $labels = collect(range($from, $to))->map(fn (int $number): string => (string) $number);
        $existing = RestaurantTable::query()
            ->where('area_id', $area->id)
            ->whereIn('normalized_label', $labels->map(fn (string $label) => LabelNormalizer::comparable($label)))
            ->get();
        $existingLabels = $existing->pluck('normalized_label');
        $newLabels = $labels->reject(
            fn (string $label): bool => $existingLabels->contains(LabelNormalizer::comparable($label)),
        )->values();

        try {
            $created = DB::transaction(function () use ($area, $newLabels): Collection {
                $nextOrder = ((int) $area->tables()->max('sort_order')) + 1;
                $models = new Collection;
                foreach ($newLabels as $index => $label) {
                    $models->push(RestaurantTable::query()->create([
                        'area_id' => $area->id,
                        'label' => $label,
                        'normalized_label' => LabelNormalizer::comparable($label),
                        'sort_order' => $nextOrder + $index,
                        'is_active' => true,
                    ]));
                }

                return $models;
            });
        } catch (QueryException $exception) {
            $this->throwDuplicate($exception, $area, 'solicitada');
        }

        return [
            'created' => $created,
            'skippedExisting' => $existing->sortBy('sort_order')->values(),
        ];
    }

    public function reorder(Area $area, array $items): Collection
    {
        $requestedIds = collect($items)->pluck('id')->sort()->values()->all();
        $currentIds = $area->tables()->pluck('id')->sort()->values()->all();
        if ($requestedIds !== $currentIds) {
            throw new ApiException('INVALID_TABLE_ORDER', 'El orden debe incluir todas las mesas del área una sola vez.', 422);
        }

        DB::transaction(function () use ($area, $items): void {
            foreach ($items as $item) {
                RestaurantTable::query()
                    ->where('area_id', $area->id)
                    ->whereKey($item['id'])
                    ->update(['sort_order' => $item['sortOrder']]);
            }
        });

        return $this->listForArea($area);
    }

    public function delete(RestaurantTable $table): void
    {
        // Future table-session references must be checked here before deletion.
        $table->delete();
    }

    private function throwDuplicate(QueryException $exception, Area $area, string $label): never
    {
        if ((string) $exception->getCode() === '23505') {
            throw new ApiException(
                'TABLE_LABEL_DUPLICATE',
                'Ya existe una Mesa '.$label.' en '.$area->name.'.',
                409,
                $exception,
            );
        }
        throw $exception;
    }
}
