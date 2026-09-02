<?php

namespace App\Domain\Orders\Services;

use App\Domain\Areas\Models\Area;
use App\Domain\Orders\Events\TableSessionMoved;
use App\Domain\Orders\Events\TableWaiterTransferred;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Orders\Models\TableSession;
use App\Domain\Orders\Support\DecimalMoney;
use App\Domain\Staff\Models\StaffMember;
use App\Domain\Tables\Models\RestaurantTable;
use App\Support\Http\ApiException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class TableSessionService
{
    public function operations(): Collection
    {
        return Area::query()->where('is_active', true)->with(['tables' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])->orderBy('sort_order')->get();
    }

    public function openSessions(): Collection
    {
        $modifierTotals = DB::table('order_item_modifiers')
            ->selectRaw('order_item_id, SUM(price_delta_snapshot) AS modifier_total')
            ->groupBy('order_item_id');
        $totals = DB::table('orders as operation_orders')
            ->join('order_items as operation_items', 'operation_items.order_id', '=', 'operation_orders.id')
            ->leftJoinSub($modifierTotals, 'operation_modifiers', 'operation_modifiers.order_item_id', '=', 'operation_items.id')
            ->where('operation_orders.status', 'CONFIRMED')
            ->where('operation_items.status', OrderItem::STATUS_ACTIVE)
            ->selectRaw('operation_orders.table_session_id, SUM((operation_items.unit_price_snapshot + COALESCE(operation_modifiers.modifier_total, 0)) * operation_items.quantity) AS total')
            ->groupBy('operation_orders.table_session_id')
            ->pluck('total', 'table_session_id');
        $sessions = TableSession::query()->where('status', TableSession::STATUS_OPEN)
            ->with(['table.area', 'waiter'])
            ->withCount(['orders as confirmed_orders_count' => fn ($query) => $query->where('status', 'CONFIRMED')])
            ->get();
        foreach ($sessions as $session) {
            $session->setAttribute('operation_total', (string) ($totals[$session->id] ?? '0'));
        }

        return $sessions;
    }

    public function hydrate(TableSession $session): TableSession
    {
        return $session->refresh()->load(['table.area', 'waiter', 'orders.waiter', 'orders.items.modifiers', 'orders.printJobs.printer', 'orders.printJobs.center', 'waiterAssignments.fromWaiter', 'waiterAssignments.toWaiter', 'tableTransfers.fromTable.area', 'tableTransfers.toTable.area']);
    }

    public function current(RestaurantTable $table): ?TableSession
    {
        $session = TableSession::query()->where('table_id', $table->id)->where('status', TableSession::STATUS_OPEN)->first();

        return $session ? $this->hydrate($session) : null;
    }

    public function totalMinor(TableSession $session): int
    {
        $session->loadMissing('orders.items.modifiers');
        $total = 0;
        foreach ($session->orders->where('status', 'CONFIRMED') as $order) {
            foreach ($order->items->where('status', OrderItem::STATUS_ACTIVE) as $item) {
                $unit = DecimalMoney::toMinor((string) $item->unit_price_snapshot) + $item->modifiers->sum(fn ($m) => DecimalMoney::toMinor((string) $m->price_delta_snapshot));
                $total += $unit * $item->quantity;
            }
        }

        return $total;
    }

    public function transferWaiter(TableSession $session, string $waiterId, ?string $reason): TableSession
    {
        DB::transaction(function () use ($session, $waiterId, $reason): void {
            $locked = TableSession::query()->lockForUpdate()->findOrFail($session->id);
            if ($locked->status !== TableSession::STATUS_OPEN) {
                throw new ApiException('TABLE_SESSION_CLOSED', 'La atención ya está cerrada.', 409);
            }
            $waiter = StaffMember::query()->where('staff_type', StaffMember::TYPE_WAITER)->find($waiterId);
            if (! $waiter) {
                throw new ApiException('WAITER_NOT_FOUND', 'El mesero seleccionado no existe.', 404);
            }
            if (! $waiter->is_active) {
                throw new ApiException('WAITER_INACTIVE', $waiter->name.' está inactivo.', 409);
            }
            if ($waiter->id === $locked->assigned_waiter_id) {
                throw new ApiException('INVALID_WAITER_TRANSFER', 'Selecciona un mesero diferente.', 422);
            }
            $from = $locked->assigned_waiter_id;
            $locked->load('table.area', 'waiter');
            $fromName = $locked->waiter->name;
            $locked->update(['assigned_waiter_id' => $waiter->id]);
            $locked->waiterAssignments()->create(['from_waiter_id' => $from, 'to_waiter_id' => $waiter->id, 'performed_by_actor_id' => null, 'reason' => $reason]);
            DB::afterCommit(fn () => event(new TableWaiterTransferred($locked->id, $locked->table->area->name, $locked->table->label, $from, $fromName, $waiter->id, $waiter->name, now()->toIso8601String())));
        });

        return $this->hydrate($session);
    }

    public function move(TableSession $session, string $targetTableId, ?string $reason): TableSession
    {
        DB::transaction(function () use ($session, $targetTableId, $reason): void {
            $locked = TableSession::query()->lockForUpdate()->findOrFail($session->id);
            if ($locked->status !== TableSession::STATUS_OPEN) {
                throw new ApiException('TABLE_SESSION_CLOSED', 'La atención ya está cerrada.', 409);
            }
            if ($locked->table_id === $targetTableId) {
                throw new ApiException('INVALID_TABLE_TRANSFER', 'Selecciona una mesa diferente.', 422);
            }
            $ids = collect([$locked->table_id, $targetTableId])->sort()->values();
            $tables = RestaurantTable::query()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $target = $tables->get($targetTableId);
            if (! $target) {
                throw new ApiException('TABLE_NOT_FOUND', 'La mesa de destino no existe.', 404);
            }
            if (! $target->is_active) {
                throw new ApiException('TARGET_TABLE_INACTIVE', 'La mesa de destino está desactivada.', 409);
            }
            if (TableSession::query()->where('table_id', $target->id)->where('status', TableSession::STATUS_OPEN)->lockForUpdate()->exists()) {
                throw new ApiException('TARGET_TABLE_OCCUPIED', 'La mesa de destino ya está ocupada.', 409);
            }
            $from = $locked->table_id;
            $locked->update(['table_id' => $target->id]);
            $locked->tableTransfers()->create(['from_table_id' => $from, 'to_table_id' => $target->id, 'performed_by_actor_id' => null, 'reason' => $reason]);
            DB::afterCommit(fn () => event(new TableSessionMoved($locked->id, $from, $target->id)));
        }, 3);

        return $this->hydrate($session);
    }
}
