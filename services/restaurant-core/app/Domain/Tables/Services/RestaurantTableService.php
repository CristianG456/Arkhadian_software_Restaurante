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
            return DB::transaction(function () use ($area, $label): RestaurantTable {
                $lockedArea = Area::query()->lockForUpdate()->findOrFail($area->id);

                return RestaurantTable::query()->create([
                    'area_id' => $lockedArea->id,
                    'label' => $label,
                    'normalized_label' => LabelNormalizer::comparable($label),
                    'sort_order' => ((int) $lockedArea->tables()->max('sort_order')) + 1,
                    'is_active' => true,
                ]);
            });
        } catch (QueryException $exception) {
            $this->throwDuplicate($exception, $area, $label);
        }
    }

    public function update(RestaurantTable $table, array $data): RestaurantTable
    {
        if (array_key_exists('label', $data)) {
            $table->label = LabelNormalizer::tableDisplay($data['label']);
            $table->normalized_label = LabelNormalizer::comparable($table->label);
        }
        if (array_key_exists('isActive', $data)) {
            $table->is_active = $data['isActive'];
        }

        try {
            $table->save();
        } catch (QueryException $exception) {
            $this->throwDuplicate($exception, $table->area, $table->label);
        }

        return $table->refresh();
    }

    public function move(RestaurantTable $table, Area $targetArea): RestaurantTable
    {
        if ($table->area_id === $targetArea->id) {
            return $table->refresh();
        }

        try {
            return DB::transaction(function () use ($table, $targetArea): RestaurantTable {
                $lockedTable = RestaurantTable::query()->lockForUpdate()->findOrFail($table->id);
                $lockedTarget = Area::query()->lockForUpdate()->findOrFail($targetArea->id);
                $lockedTable->area_id = $lockedTarget->id;
                $lockedTable->sort_order = ((int) $lockedTarget->tables()->max('sort_order')) + 1;
                $lockedTable->save();

                return $lockedTable->refresh();
            });
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23505') {
                throw new ApiException(
                    'TABLE_TARGET_AREA_CONFLICT',
                    'Ya existe una Mesa '.$table->label.' en '.$targetArea->name.'.',
                    409,
                    $exception,
                );
            }

            throw $exception;
        }
    }

    public function bulkCreate(Area $area, int $from, int $to): array
    {
        if (($to - $from) + 1 > 100) {
            throw new ApiException('TABLE_BULK_LIMIT', 'Puedes crear máximo 100 mesas por operación.', 422);
        }

        try {
            return DB::transaction(function () use ($area, $from, $to): array {
                $lockedArea = Area::query()->lockForUpdate()->findOrFail($area->id);
                $labels = collect(range($from, $to))->map(fn (int $number): string => (string) $number);
                $normalizedLabels = $labels->map(fn (string $label) => LabelNormalizer::comparable($label));
                $existing = RestaurantTable::query()
                    ->where('area_id', $lockedArea->id)
                    ->whereIn('normalized_label', $normalizedLabels)
                    ->get();
                $existingLabels = $existing->pluck('normalized_label');
                $newLabels = $labels->reject(
                    fn (string $label): bool => $existingLabels->contains(LabelNormalizer::comparable($label)),
                )->values();
                $nextOrder = ((int) $lockedArea->tables()->max('sort_order')) + 1;
                $models = new Collection;
                foreach ($newLabels as $index => $label) {
                    $models->push(RestaurantTable::query()->create([
                        'area_id' => $lockedArea->id,
                        'label' => $label,
                        'normalized_label' => LabelNormalizer::comparable($label),
                        'sort_order' => $nextOrder + $index,
                        'is_active' => true,
                    ]));
                }

                return [
                    'created' => $models,
                    'skippedExisting' => $existing->sortBy('sort_order')->values(),
                ];
            });
        } catch (QueryException $exception) {
            $this->throwDuplicate($exception, $area, 'solicitada');
        }
    }

    public function reorder(Area $area, array $items): Collection
    {
        DB::transaction(function () use ($area, $items): void {
            Area::query()->lockForUpdate()->findOrFail($area->id);
            $requestedIds = collect($items)->pluck('id')->sort()->values()->all();
            $currentIds = RestaurantTable::query()
                ->where('area_id', $area->id)
                ->lockForUpdate()
                ->pluck('id')
                ->sort()
                ->values()
                ->all();
            if ($requestedIds !== $currentIds) {
                throw new ApiException('INVALID_TABLE_ORDER', 'El orden debe incluir todas las mesas del área una sola vez.', 422);
            }

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
        if ($table->qrCredentials()->exists()) {
            throw new ApiException(
                'TABLE_HAS_QR_HISTORY',
                'No puedes eliminar '.$table->label.' porque tiene historial de QR. Desactiva la mesa para conservar sus referencias.',
                409,
            );
        }

        // Future TableSession references must also be checked here.
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
