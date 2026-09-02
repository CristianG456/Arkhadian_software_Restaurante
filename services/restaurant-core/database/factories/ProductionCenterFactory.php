<?php

namespace Database\Factories;

use App\Domain\Production\Models\ProductionCenter;
use App\Support\Text\LabelNormalizer;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductionCenterFactory extends Factory
{
    protected $model = ProductionCenter::class;

    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->word());

        return ['name' => $name, 'normalized_name' => LabelNormalizer::comparable($name), 'sort_order' => fake()->numberBetween(1, 20), 'is_active' => true];
    }
}
