<?php

namespace Database\Seeders\Development;

use App\Domain\Production\Models\ProductionCenter;
use App\Support\Text\LabelNormalizer;
use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Cocina', 'Bebidas', 'Postres'] as $index => $name) {
            ProductionCenter::query()->updateOrCreate(['normalized_name' => LabelNormalizer::comparable($name)], ['name' => $name, 'sort_order' => $index + 1, 'is_active' => true]);
        }
    }
}
