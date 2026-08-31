<?php

namespace Database\Seeders\Development;

use App\Domain\Areas\Models\Area;
use App\Domain\Tables\Models\RestaurantTable;
use App\Support\Text\LabelNormalizer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AreasAndTablesSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $areas = [
                ['name' => 'Salón principal', 'tables' => range(1, 8)],
                ['name' => 'Sala VIP', 'tables' => range(1, 4)],
                ['name' => 'Terraza', 'tables' => range(1, 6)],
            ];

            foreach ($areas as $areaIndex => $definition) {
                $area = Area::query()->updateOrCreate(
                    ['normalized_name' => LabelNormalizer::comparable($definition['name'])],
                    ['name' => $definition['name'], 'sort_order' => $areaIndex + 1, 'is_active' => true],
                );

                foreach ($definition['tables'] as $tableIndex => $number) {
                    $label = (string) $number;
                    RestaurantTable::query()->updateOrCreate(
                        ['area_id' => $area->id, 'normalized_label' => LabelNormalizer::comparable($label)],
                        ['label' => $label, 'sort_order' => $tableIndex + 1, 'is_active' => true],
                    );
                }
            }
        });
    }
}
