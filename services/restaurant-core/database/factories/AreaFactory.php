<?php

namespace Database\Factories;

use App\Domain\Areas\Models\Area;
use App\Support\Text\LabelNormalizer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Area> */
class AreaFactory extends Factory
{
    protected $model = Area::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'normalized_name' => LabelNormalizer::comparable($name),
            'sort_order' => fake()->numberBetween(1, 20),
            'is_active' => true,
        ];
    }
}
