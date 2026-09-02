<?php

namespace Tests\Feature;

use App\Domain\TableQr\Models\TableQrCredential;
use App\Domain\Tables\Models\RestaurantTable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableQrCredentialsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_opaque_active_qr_and_returns_configured_url(): void
    {
        $table = RestaurantTable::factory()->create();

        $response = $this->postJson('/api/tables/'.$table->id.'/qr')
            ->assertCreated()
            ->assertJsonPath('data.credential.tableId', $table->id)
            ->assertJsonPath('data.credential.status', 'active')
            ->assertJsonPath('data.credential.version', 1);

        $token = $response->json('data.credential.publicToken');
        $this->assertIsString($token);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]{43}$/', $token);
        $this->assertStringNotContainsString($table->id, $token);
        $this->assertSame(
            config('restaurant.table_qr_public_base_url').'/'.$token,
            $response->json('data.credential.publicUrl'),
        );
    }

    public function test_does_not_create_two_active_credentials(): void
    {
        $table = RestaurantTable::factory()->create();
        $this->postJson('/api/tables/'.$table->id.'/qr')->assertCreated();
        $this->postJson('/api/tables/'.$table->id.'/qr')
            ->assertConflict()
            ->assertJsonPath('error.code', 'QR_ALREADY_ACTIVE');

        $this->assertSame(1, $table->qrCredentials()->count());
    }

    public function test_regeneration_revokes_previous_token_immediately(): void
    {
        $table = RestaurantTable::factory()->create();
        $oldToken = $this->postJson('/api/tables/'.$table->id.'/qr')
            ->json('data.credential.publicToken');
        $new = $this->postJson('/api/tables/'.$table->id.'/qr/regenerate')
            ->assertOk()
            ->assertJsonPath('data.credential.version', 2)
            ->json('data.credential');

        $this->assertNotSame($oldToken, $new['publicToken']);
        $this->assertDatabaseHas('table_qr_credentials', [
            'restaurant_table_id' => $table->id,
            'public_token' => $oldToken,
            'status' => TableQrCredential::STATUS_REVOKED,
        ]);
        $this->assertDatabaseHas('table_qr_credentials', [
            'restaurant_table_id' => $table->id,
            'public_token' => $new['publicToken'],
            'status' => TableQrCredential::STATUS_ACTIVE,
        ]);
        $this->assertSame(1, $table->qrCredentials()->where('status', 'active')->count());
    }

    public function test_revokes_and_no_longer_returns_an_active_qr(): void
    {
        $table = RestaurantTable::factory()->create();
        $this->postJson('/api/tables/'.$table->id.'/qr')->assertCreated();
        $this->postJson('/api/tables/'.$table->id.'/qr/revoke')
            ->assertOk()
            ->assertJsonPath('data.credential.status', 'revoked');
        $this->getJson('/api/tables/'.$table->id.'/qr')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'QR_NOT_FOUND');
    }

    public function test_database_constraint_rejects_multiple_active_credentials(): void
    {
        $table = RestaurantTable::factory()->create();
        TableQrCredential::factory()->create(['restaurant_table_id' => $table->id]);

        $this->expectException(QueryException::class);
        TableQrCredential::factory()->create([
            'restaurant_table_id' => $table->id,
            'version' => 2,
        ]);
    }

    public function test_table_with_qr_history_cannot_be_deleted(): void
    {
        $table = RestaurantTable::factory()->create();
        TableQrCredential::factory()->revoked()->create(['restaurant_table_id' => $table->id]);

        $this->deleteJson('/api/tables/'.$table->id)
            ->assertConflict()
            ->assertJsonPath('error.code', 'TABLE_HAS_QR_HISTORY');
        $this->assertDatabaseHas('restaurant_tables', ['id' => $table->id]);
    }
}
