<?php

namespace App\Domain\Areas\Http\Resources;

use App\Domain\Tables\Http\Resources\RestaurantTableResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AreaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $tablesCount = (int) ($this->tables_count ?? 0);
        $activeTablesCount = (int) ($this->active_tables_count ?? 0);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'sortOrder' => $this->sort_order,
            'isActive' => $this->is_active,
            'tablesCount' => $tablesCount,
            'activeTablesCount' => $activeTablesCount,
            'inactiveTablesCount' => $tablesCount - $activeTablesCount,
            'tables' => RestaurantTableResource::collection($this->whenLoaded('tables')),
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
        ];
    }
}
