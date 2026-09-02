<?php

namespace App\Domain\Orders\Services;

use App\Domain\Catalog\Enums\ProductChannel;
use App\Domain\Orders\Enums\OrderSource;
use App\Domain\Orders\Events\OrderCancelled;
use App\Domain\Orders\Events\OrderConfirmed;
use App\Domain\Orders\Events\OrderItemCancelled;
use App\Domain\Orders\Events\TableSessionOpened;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Orders\Models\TableSession;
use App\Domain\Orders\Support\DecimalMoney;
use App\Domain\Staff\Models\StaffMember;
use App\Domain\Tables\Models\RestaurantTable;
use App\Support\Http\ApiException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class OrderService
{
    public function __construct(private readonly OrderPricingService $pricing) {}

    public function hydrate(Order $order): Order
    {
        return $order->refresh()->load(['waiter', 'items.modifiers', 'printJobs.printer', 'printJobs.center', 'session.table.area', 'session.waiter']);
    }

    public function createAdmin(array $data, OrderSource $source = OrderSource::ADMIN_POS, ProductChannel $channel = ProductChannel::ADMIN_POS): Order
    {
        $existing = Order::query()->where('client_operation_id', $data['clientOperationId'])->first();
        if ($existing) {
            return $this->hydrate($existing);
        }

        try {
            $createdSessionId = null;
            $order = DB::transaction(function () use ($data, $source, $channel, &$createdSessionId): Order {
                $table = RestaurantTable::query()->lockForUpdate()->findOrFail($data['tableId']);
                if (! $table->is_active) {
                    throw new ApiException('TABLE_INACTIVE', 'La mesa seleccionada está desactivada.', 409);
                }

                $session = TableSession::query()->where('table_id', $table->id)->where('status', TableSession::STATUS_OPEN)->lockForUpdate()->first();
                if (! $session) {
                    $waiter = $this->activeWaiter($data['assignedWaiterId'] ?? null);
                    $session = TableSession::query()->create(['table_id' => $table->id, 'assigned_waiter_id' => $waiter->id, 'status' => TableSession::STATUS_OPEN, 'opened_at' => now(), 'opened_by_actor_id' => null]);
                    $session->waiterAssignments()->create(['from_waiter_id' => null, 'to_waiter_id' => $waiter->id, 'performed_by_actor_id' => null, 'reason' => 'Apertura de mesa']);
                    $createdSessionId = $session->id;
                } else {
                    $session->load('waiter');
                    if (isset($data['assignedWaiterId']) && $data['assignedWaiterId'] !== $session->assigned_waiter_id) {
                        throw new ApiException('TABLE_ASSIGNED_TO_ANOTHER_WAITER', 'Esta mesa ya está siendo atendida por '.$session->waiter->name.'.', 409);
                    }
                }

                $plans = collect($data['items'])->map(fn (array $item) => $this->pricing->price($item, $channel));
                $order = Order::query()->create(['table_session_id' => $session->id, 'responsible_waiter_id' => $session->assigned_waiter_id, 'source' => $source, 'status' => Order::STATUS_CONFIRMED, 'client_operation_id' => $data['clientOperationId'], 'created_by_actor_id' => null]);
                foreach ($plans as $plan) {
                    $item = $order->items()->create([
                        'product_id' => $plan['product']->id, 'variant_id' => $plan['variant']->id, 'quantity' => $plan['quantity'],
                        'product_name_snapshot' => $plan['product']->name, 'variant_name_snapshot' => $plan['variant']->name,
                        'unit_price_snapshot' => DecimalMoney::fromMinor($plan['baseMinor']),
                        'line_base_total_snapshot' => DecimalMoney::fromMinor($plan['baseMinor'] * $plan['quantity']),
                        'notes' => $plan['notes'], 'status' => OrderItem::STATUS_ACTIVE,
                    ]);
                    foreach ($plan['modifiers'] as $modifier) {
                        $item->modifiers()->create([
                            'modifier_group_id' => $modifier['group']->id, 'modifier_option_id' => $modifier['option']->id,
                            'group_name_snapshot' => $modifier['group']->name, 'option_name_snapshot' => $modifier['option']->name,
                            'price_delta_snapshot' => (string) $modifier['option']->price_delta,
                        ]);
                    }
                }
                DB::afterCommit(fn () => event(new OrderConfirmed($order->id)));
                if ($createdSessionId) {
                    DB::afterCommit(fn () => event(new TableSessionOpened($createdSessionId)));
                }

                return $order;
            }, 3);
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23505') {
                $existing = Order::query()->where('client_operation_id', $data['clientOperationId'])->first();
                if ($existing) {
                    return $this->hydrate($existing);
                }
                throw new ApiException('TABLE_ALREADY_OCCUPIED', 'La mesa fue ocupada por otra operación.', 409, $exception);
            }
            throw $exception;
        }

        return $this->hydrate($order);
    }

    public function createWaiter(array $data, StaffMember $waiter): Order
    {
        if (! $waiter->is_active) {
            throw new ApiException('WAITER_INACTIVE', $waiter->name.' está inactivo.', 409);
        }
        $data['assignedWaiterId'] = $waiter->id;

        return $this->createAdmin($data, OrderSource::WAITER, ProductChannel::WAITER);
    }

    public function cancelItem(OrderItem $item, string $reason): OrderItem
    {
        DB::transaction(function () use ($item, $reason): void {
            $locked = OrderItem::query()->lockForUpdate()->findOrFail($item->id);
            if ($locked->status === OrderItem::STATUS_CANCELLED) {
                throw new ApiException('ORDER_ITEM_ALREADY_CANCELLED', 'Este producto ya fue cancelado.', 409);
            }
            $locked->update(['status' => OrderItem::STATUS_CANCELLED, 'cancelled_at' => now(), 'cancelled_by_actor_id' => null, 'cancel_reason' => $reason]);
            DB::afterCommit(fn () => event(new OrderItemCancelled($locked->id)));
        });

        return $item->refresh()->load('modifiers');
    }

    public function cancelOrder(Order $order, string $reason): Order
    {
        DB::transaction(function () use ($order, $reason): void {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            if ($locked->status === Order::STATUS_CANCELLED) {
                throw new ApiException('ORDER_ALREADY_CANCELLED', 'Este pedido ya fue cancelado.', 409);
            }
            $locked->update(['status' => Order::STATUS_CANCELLED, 'cancelled_at' => now(), 'cancel_reason' => $reason]);
            $locked->items()->where('status', OrderItem::STATUS_ACTIVE)->update(['status' => OrderItem::STATUS_CANCELLED, 'cancelled_at' => now(), 'cancelled_by_actor_id' => null, 'cancel_reason' => $reason]);
            DB::afterCommit(fn () => event(new OrderCancelled($locked->id)));
        });

        return $this->hydrate($order);
    }

    private function activeWaiter(?string $id): StaffMember
    {
        if (! $id) {
            throw new ApiException('WAITER_NOT_FOUND', 'Selecciona el mesero responsable.', 422);
        }
        $waiter = StaffMember::query()->where('staff_type', StaffMember::TYPE_WAITER)->find($id);
        if (! $waiter) {
            throw new ApiException('WAITER_NOT_FOUND', 'El mesero seleccionado no existe.', 404);
        }
        if (! $waiter->is_active) {
            throw new ApiException('WAITER_INACTIVE', $waiter->name.' está inactivo.', 409);
        }

        return $waiter;
    }
}
