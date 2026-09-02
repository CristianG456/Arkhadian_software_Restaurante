<?php

namespace Database\Factories;

use App\Domain\Orders\Enums\OrderSource;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\TableSession;
use App\Domain\Staff\Models\StaffMember;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return ['table_session_id' => TableSession::factory(), 'responsible_waiter_id' => StaffMember::factory(), 'source' => OrderSource::ADMIN_POS, 'status' => Order::STATUS_CONFIRMED, 'client_operation_id' => (string) Str::ulid()];
    }
}
