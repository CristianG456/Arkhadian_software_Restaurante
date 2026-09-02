<?php

namespace Database\Factories;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductVariant;
use App\Support\Text\LabelNormalizer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductVariant> */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->word());

        return [
            'product_id' => Product::factory(),
            'name' => $name,
            'normalized_name' => LabelNormalizer::comparable($name),
            'price' => fake()->randomElement(['5000.00', '18000.00', '29000.00']),
            'is_default' => false,
            'is_active' => true,
            'is_available' => true,
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }

    public function default(): static
    {
        return $this->state(fn (): array => [
            'name' => null,
            'normalized_name' => null,
            'is_default' => true,
            'sort_order' => 1,
        ]);
    }
}
