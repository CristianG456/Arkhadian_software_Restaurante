<?php

namespace Database\Seeders\Development;

use App\Domain\Staff\Models\StaffMember;
use App\Support\Text\LabelNormalizer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Cristian' => 'cristian', 'Andrés' => 'andres', 'Camila' => 'camila'] as $name => $username) {
            StaffMember::query()->updateOrCreate(['normalized_name' => LabelNormalizer::comparable($name)], ['name' => $name, 'staff_type' => StaffMember::TYPE_WAITER, 'is_active' => true, 'username' => $username, 'pin_hash' => Hash::make('1234')]);
        }
    }
}
