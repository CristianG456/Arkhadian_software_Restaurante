<?php

namespace App\Domain\Areas\Services;

use App\Domain\Areas\Models\Area;
use App\Support\Http\ApiException;
use App\Support\Text\LabelNormalizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class AreaService
{
    public function listWithTables(): Collection
    {
        return Area::query()
            ->withCount([
                'tables',
                'tables as active_tables_count' => fn (Builder $query) => $query->where('is_active', true),
            ])
            ->with('tables')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function create(string $name): Area
    {
        $name = LabelNormalizer::display($name);

        try {
            $area = DB::transaction(fn (): Area => Area::query()->create([
                'name' => $name,
                'normalized_name' => LabelNormalizer::comparable($name),
                'sort_order' => ((int) Area::query()->max('sort_order')) + 1,
                'is_active' => true,
            ]));
        } catch (QueryException $exception) {
            $this->throwDuplicateName($exception);
        }

        return $this->hydrate($area);
    }

    public function update(Area $area, array $data): Area
    {
        if (array_key_exists('name', $data)) {
            $name = LabelNormalizer::display($data['name']);
            $area->name = $name;
            $area->normalized_name = LabelNormalizer::comparable($name);
        }
        if (array_key_exists('isActive', $data)) {
            $area->is_active = $data['isActive'];
        }

        try {
            $area->save();
        } catch (QueryException $exception) {
            $this->throwDuplicateName($exception);
        }

        return $this->hydrate($area);
    }

    public function delete(Area $area): void
    {
        $tablesCount = $area->tables()->count();
        if ($tablesCount > 0) {
            throw new ApiException(
                'AREA_HAS_TABLES',
                'No puedes eliminar '.$area->name.' porque contiene '.$tablesCount.' mesas. Desactiva el área para conservar sus datos.',
                409,
            );
        }
        $area->delete();
    }

    public function hydrate(Area $area): Area
    {
        return $area->refresh()
            ->loadCount([
                'tables',
                'tables as active_tables_count' => fn (Builder $query) => $query->where('is_active', true),
            ])
            ->load('tables');
    }

    public function reorder(array $items): Collection
    {
        $requestedIds = collect($items)->pluck('id')->sort()->values()->all();
        $currentIds = Area::query()->pluck('id')->sort()->values()->all();
        if ($requestedIds !== $currentIds) {
            throw new ApiException('INVALID_AREA_ORDER', 'El orden debe incluir todas las áreas una sola vez.', 422);
        }

        DB::transaction(function () use ($items): void {
            foreach ($items as $item) {
                Area::query()->whereKey($item['id'])->update(['sort_order' => $item['sortOrder']]);
            }
        });

        return $this->listWithTables();
    }

    private function throwDuplicateName(QueryException $exception): never
    {
        if ((string) $exception->getCode() === '23505') {
            throw new ApiException('AREA_NAME_DUPLICATE', 'Ya existe un área con ese nombre.', 409, $exception);
        }
        throw $exception;
    }
}
