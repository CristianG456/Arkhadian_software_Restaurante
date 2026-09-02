<?php

namespace Database\Factories;

use App\Domain\TableQr\Models\TableQrCredential;
use App\Domain\Tables\Models\RestaurantTable;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TableQrCredential> */
class TableQrCredentialFactory extends Factory
{
    protected $model = TableQrCredential::class;

    public function definition(): array
    {
        return [
            'restaurant_table_id' => RestaurantTable::factory(),
            'public_token' => rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='),
            'version' => 1,
            'status' => TableQrCredential::STATUS_ACTIVE,
            'revoked_at' => null,
        ];
    }

    public function revoked(): static
    {
        return $this->state(fn (): array => [
            'status' => TableQrCredential::STATUS_REVOKED,
            'revoked_at' => now(),
        ]);
    }
}
