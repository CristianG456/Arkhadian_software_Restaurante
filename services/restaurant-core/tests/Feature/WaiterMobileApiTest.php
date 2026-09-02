<?php

namespace Tests\Feature;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductCategory;
use App\Domain\Catalog\Models\ProductVariant;
use App\Domain\Mobile\Models\DevicePairing;
use App\Domain\Mobile\Models\WaiterDevice;
use App\Domain\Orders\Models\TableSession;
use App\Domain\Staff\Models\StaffMember;
use App\Domain\Tables\Models\RestaurantTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class WaiterMobileApiTest extends TestCase
{
    use RefreshDatabase;

    private function pairing(): array
    {
        $pairing = $this->postJson('/api/mobile/pairings')->assertCreated()->json('data.pairing');
        $deviceId = (string) Str::uuid();
        $paired = $this->postJson('/api/device-pairing/pair', ['restaurantCode' => $pairing['restaurantCode'], 'pairingCode' => $pairing['pairingCode'], 'deviceName' => 'Samsung Prueba', 'deviceIdentifier' => $deviceId, 'platform' => 'ANDROID'])->assertCreated()->json('data');

        return [$pairing, $paired];
    }

    private function waiter(string $username = 'mobile_andres', string $pin = '4281'): StaffMember
    {
        return StaffMember::factory()->create(['username' => $username, 'pin_hash' => Hash::make($pin), 'is_active' => true]);
    }

    private function login(StaffMember $waiter, array $paired, string $pin = '4281'): array
    {
        $response = $this->withHeaders(['X-Device-Token' => $paired['deviceToken']])->postJson('/api/auth/waiter/login', ['username' => $waiter->username, 'pin' => $pin])->assertOk();

        return ['X-Device-Token' => $paired['deviceToken'], 'Authorization' => 'Bearer '.$response->json('data.accessToken')];
    }

    private function product(): array
    {
        $category = ProductCategory::factory()->create(['is_active' => true]);
        $product = Product::factory()->create(['category_id' => $category->id, 'is_active' => true, 'is_available' => true]);
        $variant = ProductVariant::factory()->default()->create(['product_id' => $product->id, 'price' => '25000.00', 'is_available' => true]);
        $product->channels()->create(['channel' => 'WAITER', 'is_visible' => true]);

        return [$product, $variant];
    }

    public function test_pairing_validates_invalid_expired_used_and_revocation(): void
    {
        $pairing = $this->postJson('/api/mobile/pairings')->assertCreated()->json('data.pairing');
        $invalid = $this->postJson('/api/device-pairing/pair', ['restaurantCode' => $pairing['restaurantCode'], 'pairingCode' => '000000', 'deviceName' => 'Teléfono', 'deviceIdentifier' => (string) Str::uuid(), 'platform' => 'ANDROID']);
        $this->assertSame(422, $invalid->status(), 'El código inválido debe rechazarse.');
        $invalid->assertJsonPath('error.code', 'PAIRING_CODE_INVALID');
        DevicePairing::query()->whereKey($pairing['id'])->update(['expires_at' => now()->subMinute()]);
        $expired = $this->postJson('/api/device-pairing/pair', ['restaurantCode' => $pairing['restaurantCode'], 'pairingCode' => $pairing['pairingCode'], 'deviceName' => 'Teléfono', 'deviceIdentifier' => (string) Str::uuid(), 'platform' => 'ANDROID']);
        $this->assertSame(422, $expired->status(), 'El código vencido debe rechazarse.');
        $expired->assertJsonPath('error.code', 'PAIRING_CODE_EXPIRED');

        [$valid, $paired] = $this->pairing();
        $this->postJson('/api/device-pairing/pair', ['restaurantCode' => $valid['restaurantCode'], 'pairingCode' => $valid['pairingCode'], 'deviceName' => 'Otro', 'deviceIdentifier' => (string) Str::uuid(), 'platform' => 'ANDROID'])->assertConflict()->assertJsonPath('error.code', 'PAIRING_CODE_USED');
        $device = WaiterDevice::query()->findOrFail($paired['device']['id']);
        $this->postJson('/api/mobile/devices/'.$device->id.'/revoke')->assertOk();
        $this->withHeaders(['X-Device-Token' => $paired['deviceToken']])->postJson('/api/auth/waiter/login', ['username' => 'nadie', 'pin' => '1234'])->assertUnauthorized()->assertJsonPath('error.code', 'DEVICE_REVOKED');
    }

    public function test_login_requires_correct_pin_and_active_waiter(): void
    {
        [, $paired] = $this->pairing();
        $waiter = $this->waiter();
        $this->withHeaders(['X-Device-Token' => $paired['deviceToken']])->postJson('/api/auth/waiter/login', ['username' => $waiter->username, 'pin' => '9999'])->assertUnauthorized()->assertJsonPath('error.code', 'INVALID_CREDENTIALS');
        $headers = $this->login($waiter, $paired);
        $this->withHeaders($headers)->getJson('/api/auth/me')->assertOk()->assertJsonPath('data.user.id', $waiter->id)->assertJsonMissingPath('data.user.pin');
        $waiter->update(['is_active' => false]);
        $this->withHeaders($headers)->getJson('/api/auth/me')->assertForbidden()->assertJsonPath('error.code', 'WAITER_INACTIVE');
    }

    public function test_login_is_rate_limited_per_client(): void
    {
        [, $paired] = $this->pairing();
        $waiter = $this->waiter();
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.77']);
        $headers = ['X-Device-Token' => $paired['deviceToken']];

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->withHeaders($headers)->postJson('/api/auth/waiter/login', ['username' => $waiter->username, 'pin' => '9999'])->assertUnauthorized();
        }

        $this->withHeaders($headers)->postJson('/api/auth/waiter/login', ['username' => $waiter->username, 'pin' => '9999'])
            ->assertTooManyRequests()
            ->assertJsonPath('error.code', 'RATE_LIMITED');
    }

    public function test_waiter_sees_own_tables_and_cannot_operate_another_waiters_session(): void
    {
        [, $firstDevice] = $this->pairing();
        $first = $this->waiter('mobile_first');
        $firstHeaders = $this->login($first, $firstDevice);
        [$product, $variant] = $this->product();
        $table = RestaurantTable::factory()->create();
        $payload = ['clientOperationId' => (string) Str::ulid(), 'items' => [['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 2, 'modifierOptionIds' => []]]];
        $this->withHeaders($firstHeaders)->postJson('/api/waiter/tables/'.$table->id.'/orders', $payload)->assertCreated();
        $session = TableSession::query()->where('table_id', $table->id)->firstOrFail();
        $this->withHeaders($firstHeaders)->getJson('/api/waiter/me/tables')->assertOk()->assertJsonCount(1, 'data.tables')->assertJsonPath('data.tables.0.currentTotal', '50000.00');

        [, $secondDevice] = $this->pairing();
        $second = $this->waiter('mobile_second');
        $secondHeaders = $this->login($second, $secondDevice);
        $this->withHeaders($secondHeaders)->getJson('/api/waiter/table-sessions/'.$session->id)->assertForbidden()->assertJsonPath('error.code', 'TABLE_ASSIGNED_TO_ANOTHER_WAITER');
        $this->withHeaders($secondHeaders)->postJson('/api/waiter/tables/'.$table->id.'/orders', ['clientOperationId' => (string) Str::ulid(), 'items' => $payload['items']])->assertConflict()->assertJsonPath('error.code', 'TABLE_ASSIGNED_TO_ANOTHER_WAITER');
    }

    public function test_transfer_and_move_keep_the_same_session_and_orders(): void
    {
        [, $paired] = $this->pairing();
        $from = $this->waiter('mobile_from');
        $to = $this->waiter('mobile_to');
        $headers = $this->login($from, $paired);
        [$product, $variant] = $this->product();
        $fromTable = RestaurantTable::factory()->create();
        $target = RestaurantTable::factory()->create();
        $orderId = $this->withHeaders($headers)->postJson('/api/waiter/tables/'.$fromTable->id.'/orders', ['clientOperationId' => (string) Str::ulid(), 'items' => [['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 1, 'modifierOptionIds' => []]]])->assertCreated()->json('data.order.id');
        $session = TableSession::query()->where('table_id', $fromTable->id)->firstOrFail();
        $this->withHeaders($headers)->postJson('/api/waiter/table-sessions/'.$session->id.'/move', ['targetTableId' => $target->id])->assertOk()->assertJsonPath('data.session.id', $session->id);
        $this->assertDatabaseHas('orders', ['id' => $orderId, 'table_session_id' => $session->id]);
        $this->withHeaders($headers)->postJson('/api/waiter/table-sessions/'.$session->id.'/transfer', ['waiterId' => $to->id])->assertOk()->assertJsonPath('data.session.waiter.id', $to->id);
        $this->withHeaders($headers)->getJson('/api/waiter/table-sessions/'.$session->id)->assertForbidden();
        $this->assertDatabaseHas('waiter_notifications', ['recipient_staff_id' => $to->id, 'type' => 'TABLE_TRANSFERRED']);
    }

    public function test_preaccount_and_reprint_obey_permissions_without_closing_session(): void
    {
        [, $paired] = $this->pairing();
        $waiter = $this->waiter();
        $headers = $this->login($waiter, $paired);
        [$product, $variant] = $this->product();
        $table = RestaurantTable::factory()->create();
        $orderId = $this->withHeaders($headers)->postJson('/api/waiter/tables/'.$table->id.'/orders', ['clientOperationId' => (string) Str::ulid(), 'items' => [['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 1, 'modifierOptionIds' => []]]])->assertCreated()->json('data.order.id');
        $session = TableSession::query()->where('table_id', $table->id)->firstOrFail();
        $this->withHeaders($headers)->getJson('/api/waiter/table-sessions/'.$session->id.'/preaccount')->assertOk()->assertJsonPath('data.preaccount.notice', 'PRECUENTA · NO ES FACTURA');
        $this->withHeaders($headers)->postJson('/api/waiter/table-sessions/'.$session->id.'/request-bill')->assertOk();
        $this->assertSame('OPEN', $session->refresh()->status);
        $this->withHeaders($headers)->postJson('/api/waiter/orders/'.$orderId.'/reprint')->assertForbidden()->assertJsonPath('error.code', 'PERMISSION_DENIED');
        $waiter->permissions()->create(['permission' => 'orders.reprint', 'is_granted' => true]);
        $this->withHeaders($headers)->postJson('/api/waiter/orders/'.$orderId.'/reprint')->assertCreated();
    }
}
