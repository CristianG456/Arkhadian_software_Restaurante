<?php

namespace Database\Factories;

use App\Domain\Catalog\Models\ProductCategory;
use App\Support\Text\LabelNormalizer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductCategory> */
class ProductCategoryFactory extends Factory
{
    protected $model = ProductCategory::class;

    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->words(2, true));

        return [
            'name' => $name,
            'normalized_name' => LabelNormalizer::comparable($name),
            'description' => null,
            'sort_order' => fake()->numberBetween(1, 50),
            'is_active' => true,
        ];
    }
}
