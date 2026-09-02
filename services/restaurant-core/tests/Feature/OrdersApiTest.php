<?php

namespace Tests\Feature;

use App\Domain\Catalog\Models\ModifierGroup;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductVariant;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Orders\Models\TableSession;
use App\Domain\Staff\Models\StaffMember;
use App\Domain\Tables\Models\RestaurantTable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrdersApiTest extends TestCase
{
    use RefreshDatabase;

    private function sellableProduct(string $price = '12000.00'): array
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->default()->create(['product_id' => $product->id, 'price' => $price]);
        $product->channels()->create(['channel' => 'ADMIN_POS', 'is_visible' => true]);

        return [$product, $variant];
    }

    private function payload(RestaurantTable $table, StaffMember $waiter, Product $product, ProductVariant $variant, array $extra = []): array
    {
        return ['tableId' => $table->id, 'assignedWaiterId' => $waiter->id, 'clientOperationId' => (string) Str::ulid(), 'items' => [array_merge(['productId' => $product->id, 'variantId' => $variant->id, 'quantity' => 2, 'modifierOptionIds' => [], 'notes' => 'Sin cebolla'], $extra)]];
    }

    public function test_first_order_opens_session_and_uses_server_price_snapshots(): void
    {
        $table = RestaurantTable::factory()->create();
        $waiter = StaffMember::factory()->create();
        [$product, $variant] = $this->sellableProduct('12500.00');
        $response = $this->postJson('/api/orders', $this->payload($table, $waiter, $product, $variant));
        $response->assertCreated()->assertJsonPath('data.order.source', 'ADMIN_POS')->assertJsonPath('data.order.items.0.unitPrice', '12500.00')->assertJsonPath('data.order.items.0.lineTotal', '25000.00');
        $this->assertDatabaseHas('table_sessions', ['table_id' => $table->id, 'assigned_waiter_id' => $waiter->id, 'status' => 'OPEN']);
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_operation_is_idempotent_and_second_order_reuses_session(): void
    {
        $table = RestaurantTable::factory()->create();
        $waiter = StaffMember::factory()->create();
        [$product, $variant] = $this->sellableProduct();
        $payload = $this->payload($table, $waiter, $product, $variant);
        $firstId = $this->postJson('/api/orders', $payload)->assertCreated()->json('data.order.id');
        $this->postJson('/api/orders', $payload)->assertCreated()->assertJsonPath('data.order.id', $firstId);
        $second = $this->payload($table, $waiter, $product, $variant);
        $this->postJson('/api/orders', $second)->assertCreated();
        $this->assertDatabaseCount('table_sessions', 1);
        $this->assertDatabaseCount('orders', 2);
    }

    public function test_client_cannot_send_prices_or_use_an_inactive_waiter(): void
    {
        $table = RestaurantTable::factory()->create();
        $waiter = StaffMember::factory()->inactive()->create();
        [$product, $variant] = $this->sellableProduct();
        $payload = $this->payload($table, $waiter, $product, $variant);
        $payload['items'][0]['unitPrice'] = '1.00';
        $this->postJson('/api/orders', $payload)->assertUnprocessable()->assertJsonPath('error.code', 'VALIDATION_ERROR');
        unset($payload['items'][0]['unitPrice']);
        $this->postJson('/api/orders', $payload)->assertStatus(409)->assertJsonPath('error.code', 'WAITER_INACTIVE');
    }

    public function test_modifier_rules_and_snapshots_are_enforced(): void
    {
        $table = RestaurantTable::factory()->create();
        $waiter = StaffMember::factory()->create();
        [$product, $variant] = $this->sellableProduct('10000');
        $group = ModifierGroup::factory()->create(['name' => 'Salsas']);
        $option = $group->options()->create(['name' => 'BBQ', 'normalized_name' => 'bbq', 'price_delta' => '2500.00', 'sort_order' => 1, 'is_active' => true]);
        $product->modifierAssignments()->create(['modifier_group_id' => $group->id, 'min_select' => 1, 'max_select' => 1, 'sort_order' => 1, 'is_active' => true]);
        $this->postJson('/api/orders', $this->payload($table, $waiter, $product, $variant))->assertUnprocessable()->assertJsonPath('error.code', 'INVALID_MODIFIER_SELECTION');
        $payload = $this->payload($table, $waiter, $product, $variant, ['modifierOptionIds' => [$option->id], 'quantity' => 1]);
        $this->postJson('/api/orders', $payload)->assertCreated()->assertJsonPath('data.order.items.0.unitTotal', '12500.00');
        $this->assertDatabaseHas('order_item_modifiers', ['option_name_snapshot' => 'BBQ', 'price_delta_snapshot' => '2500.00']);
    }

    public function test_waiter_transfer_and_table_move_keep_history_and_prior_order_waiter(): void
    {
        $from = RestaurantTable::factory()->create();
        $to = RestaurantTable::factory()->create(['area_id' => $from->area_id]);
        $first = StaffMember::factory()->create();
        $second = StaffMember::factory()->create();
        [$product, $variant] = $this->sellableProduct();
        $this->postJson('/api/orders', $this->payload($from, $first, $product, $variant))->assertCreated();
        $session = TableSession::query()->firstOrFail();
        $order = Order::query()->firstOrFail();
        $this->postJson('/api/table-sessions/'.$session->id.'/transfer-waiter', ['waiterId' => $second->id, 'reason' => 'Cambio de turno'])->assertOk()->assertJsonPath('data.session.waiter.id', $second->id);
        $this->postJson('/api/table-sessions/'.$session->id.'/move', ['targetTableId' => $to->id, 'reason' => 'Comodidad'])->assertOk()->assertJsonPath('data.session.table.id', $to->id);
        $this->assertSame($first->id, $order->refresh()->responsible_waiter_id);
        $this->assertDatabaseHas('table_waiter_assignments', ['from_waiter_id' => $first->id, 'to_waiter_id' => $second->id]);
        $this->assertDatabaseHas('table_session_table_transfers', ['from_table_id' => $from->id, 'to_table_id' => $to->id]);
    }

    public function test_item_and_order_cancellation_are_traceable_and_change_total(): void
    {
        $table = RestaurantTable::factory()->create();
        $waiter = StaffMember::factory()->create();
        [$product, $variant] = $this->sellableProduct();
        $orderId = $this->postJson('/api/orders', $this->payload($table, $waiter, $product, $variant))->assertCreated()->json('data.order.id');
        $item = OrderItem::query()->firstOrFail();
        $this->postJson('/api/order-items/'.$item->id.'/cancel', ['reason' => 'Error de digitación'])->assertOk()->assertJsonPath('data.item.status', 'CANCELLED');
        $this->postJson('/api/orders/'.$orderId.'/cancel', ['reason' => 'Cliente se retiró'])->assertOk()->assertJsonPath('data.order.status', 'CANCELLED');
        $this->getJson('/api/tables/'.$table->id.'/current-session')->assertOk()->assertJsonPath('data.session.total', '0.00');
    }

    public function test_waiter_with_an_open_session_cannot_be_deactivated(): void
    {
        $table = RestaurantTable::factory()->create();
        $waiter = StaffMember::factory()->create();
        [$product, $variant] = $this->sellableProduct();
        $this->postJson('/api/orders', $this->payload($table, $waiter, $product, $variant))->assertCreated();
        $this->putJson('/api/staff/'.$waiter->id, ['isActive' => false])->assertStatus(409)->assertJsonPath('error.code', 'WAITER_HAS_OPEN_SESSIONS');
    }

    public function test_database_prevents_two_open_sessions_for_the_same_table(): void
    {
        $table = RestaurantTable::factory()->create();
        TableSession::factory()->create(['table_id' => $table->id]);
        $this->expectException(QueryException::class);
        TableSession::factory()->create(['table_id' => $table->id]);
    }

    public function test_inactive_table_and_another_waiter_are_rejected(): void
    {
        $table = RestaurantTable::factory()->create(['is_active' => false]);
        $first = StaffMember::factory()->create();
        $second = StaffMember::factory()->create();
        [$product, $variant] = $this->sellableProduct();
        $this->postJson('/api/orders', $this->payload($table, $first, $product, $variant))
            ->assertStatus(409)->assertJsonPath('error.code', 'TABLE_INACTIVE');
        $table->update(['is_active' => true]);
        $this->postJson('/api/orders', $this->payload($table, $first, $product, $variant))->assertCreated();
        $this->postJson('/api/orders', $this->payload($table, $second, $product, $variant))
            ->assertStatus(409)->assertJsonPath('error.code', 'TABLE_ASSIGNED_TO_ANOTHER_WAITER');
    }

    public function test_historical_price_does_not_change_when_catalog_price_changes(): void
    {
        $table = RestaurantTable::factory()->create();
        $waiter = StaffMember::factory()->create();
        [$product, $variant] = $this->sellableProduct('45000.00');
        $order = $this->postJson('/api/orders', $this->payload($table, $waiter, $product, $variant, ['quantity' => 1]))->assertCreated()->json('data.order.id');
        $variant->update(['price' => '52000.00']);
        $this->getJson('/api/orders/'.$order)->assertOk()
            ->assertJsonPath('data.order.items.0.unitPrice', '45000.00')
            ->assertJsonPath('data.order.items.0.lineTotal', '45000.00');
    }

    public function test_move_to_an_occupied_table_and_transfer_to_inactive_waiter_are_rejected(): void
    {
        $from = RestaurantTable::factory()->create();
        $occupied = RestaurantTable::factory()->create();
        $waiter = StaffMember::factory()->create();
        $inactive = StaffMember::factory()->inactive()->create();
        [$product, $variant] = $this->sellableProduct();
        $this->postJson('/api/orders', $this->payload($from, $waiter, $product, $variant))->assertCreated();
        TableSession::factory()->create(['table_id' => $occupied->id]);
        $session = TableSession::query()->where('table_id', $from->id)->firstOrFail();
        $this->postJson('/api/table-sessions/'.$session->id.'/move', ['targetTableId' => $occupied->id])
            ->assertStatus(409)->assertJsonPath('error.code', 'TARGET_TABLE_OCCUPIED');
        $this->postJson('/api/table-sessions/'.$session->id.'/transfer-waiter', ['waiterId' => $inactive->id])
            ->assertStatus(409)->assertJsonPath('error.code', 'WAITER_INACTIVE');
    }

    public function test_operational_read_model_returns_summary_without_detail_requests(): void
    {
        $table = RestaurantTable::factory()->create();
        $waiter = StaffMember::factory()->create();
        [$product, $variant] = $this->sellableProduct('8000.00');
        $this->postJson('/api/orders', $this->payload($table, $waiter, $product, $variant))->assertCreated();
        $this->getJson('/api/operations/tables')->assertOk()
            ->assertJsonFragment(['id' => $table->id, 'state' => 'OCCUPIED'])
            ->assertJsonFragment(['ordersCount' => 1, 'total' => '16000.00']);
    }
}
