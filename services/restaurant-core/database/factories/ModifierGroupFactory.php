<?php

namespace Database\Factories;

use App\Domain\Catalog\Models\ModifierGroup;
use App\Support\Text\LabelNormalizer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ModifierGroup> */
class ModifierGroupFactory extends Factory
{
    protected $model = ModifierGroup::class;

    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->words(2, true));

        return [
            'name' => $name,
            'normalized_name' => LabelNormalizer::comparable($name),
            'is_active' => true,
        ];
    }
}
