<?php

namespace Database\Factories;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductCategory;
use App\Support\Text\LabelNormalizer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->words(3, true));

        return [
            'category_id' => ProductCategory::factory(),
            'name' => $name,
            'normalized_name' => LabelNormalizer::comparable($name),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
            'is_available' => true,
            'allow_notes' => true,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}
