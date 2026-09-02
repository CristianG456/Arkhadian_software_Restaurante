<?php

namespace Database\Factories;

use App\Domain\Catalog\Models\ModifierGroup;
use App\Domain\Catalog\Models\ModifierOption;
use App\Support\Text\LabelNormalizer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ModifierOption> */
class ModifierOptionFactory extends Factory
{
    protected $model = ModifierOption::class;

    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->words(2, true));

        return [
            'modifier_group_id' => ModifierGroup::factory(),
            'name' => $name,
            'normalized_name' => LabelNormalizer::comparable($name),
            'price_delta' => '0.00',
            'sort_order' => fake()->numberBetween(1, 20),
            'is_active' => true,
        ];
    }
}
