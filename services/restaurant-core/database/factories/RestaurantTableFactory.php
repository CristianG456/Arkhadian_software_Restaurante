<?php

namespace Database\Factories;

use App\Domain\Areas\Models\Area;
use App\Domain\Tables\Models\RestaurantTable;
use App\Support\Text\LabelNormalizer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RestaurantTable> */
class RestaurantTableFactory extends Factory
{
    protected $model = RestaurantTable::class;

    public function definition(): array
    {
        $label = (string) fake()->unique()->numberBetween(1, 9999);

        return [
            'area_id' => Area::factory(),
            'label' => $label,
            'normalized_label' => LabelNormalizer::comparable($label),
            'sort_order' => fake()->numberBetween(1, 100),
            'is_active' => true,
        ];
    }
}
