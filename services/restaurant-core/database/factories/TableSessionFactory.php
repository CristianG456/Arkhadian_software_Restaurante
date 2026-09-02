<?php

namespace Database\Factories;

use App\Domain\Orders\Models\TableSession;
use App\Domain\Staff\Models\StaffMember;
use App\Domain\Tables\Models\RestaurantTable;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TableSession> */
class TableSessionFactory extends Factory
{
    protected $model = TableSession::class;

    public function definition(): array
    {
        return ['table_id' => RestaurantTable::factory(), 'assigned_waiter_id' => StaffMember::factory(), 'status' => TableSession::STATUS_OPEN, 'opened_at' => now()];
    }
}
