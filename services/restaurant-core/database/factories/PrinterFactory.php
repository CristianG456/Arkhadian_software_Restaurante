<?php

namespace Database\Factories;

use App\Domain\Production\Models\Printer;
use App\Support\Text\LabelNormalizer;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrinterFactory extends Factory
{
    protected $model = Printer::class;

    public function definition(): array
    {
        $name = 'Test '.fake()->unique()->word();

        return ['name' => $name, 'normalized_name' => LabelNormalizer::comparable($name), 'driver_mode' => Printer::DRIVER_TEST_FILE, 'connection_type' => 'FILE', 'paper_width' => 80, 'is_active' => true, 'is_default_fallback' => false, 'is_preaccount_default' => false];
    }
}
