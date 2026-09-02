<?php

namespace Database\Factories;

use App\Domain\Staff\Models\StaffMember;
use App\Support\Text\LabelNormalizer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StaffMember> */
class StaffMemberFactory extends Factory
{
    protected $model = StaffMember::class;

    public function definition(): array
    {
        $name = fake()->unique()->name();

        return ['name' => $name, 'normalized_name' => LabelNormalizer::comparable($name), 'staff_type' => StaffMember::TYPE_WAITER, 'is_active' => true];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
