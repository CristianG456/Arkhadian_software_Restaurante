<?php

namespace App\Domain\Orders\Http\Resources;

use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Orders\Models\TableSession;
use App\Domain\Orders\Services\TableSessionService;
use App\Domain\Orders\Support\DecimalMoney;
use App\Domain\Production\Http\Resources\ProductionResources;
use App\Domain\Staff\Models\StaffMember;

final class OrderResources
{
    public static function staff(StaffMember $staff): array
    {
        return ['id' => $staff->id, 'name' => $staff->name, 'username' => $staff->username, 'type' => $staff->staff_type, 'isActive' => $staff->is_active];
    }

    public static function item(OrderItem $item): array
    {
        $modifierMinor = $item->modifiers->sum(fn ($modifier) => DecimalMoney::toMinor((string) $modifier->price_delta_snapshot));
        $unitMinor = DecimalMoney::toMinor((string) $item->unit_price_snapshot) + $modifierMinor;

        return ['id' => $item->id, 'productId' => $item->product_id, 'variantId' => $item->variant_id, 'productName' => $item->product_name_snapshot, 'variantName' => $item->variant_name_snapshot, 'quantity' => $item->quantity, 'unitPrice' => (string) $item->unit_price_snapshot, 'unitTotal' => DecimalMoney::fromMinor($unitMinor), 'lineTotal' => DecimalMoney::fromMinor($unitMinor * $item->quantity), 'notes' => $item->notes, 'status' => $item->status, 'cancelledAt' => $item->cancelled_at?->toIso8601String(), 'cancelReason' => $item->cancel_reason, 'modifiers' => $item->modifiers->map(fn ($modifier) => ['id' => $modifier->id, 'groupName' => $modifier->group_name_snapshot, 'optionName' => $modifier->option_name_snapshot, 'priceDelta' => (string) $modifier->price_delta_snapshot])->values()->all()];
    }

    public static function order(Order $order): array
    {
        return ['id' => $order->id, 'orderNumber' => $order->order_number, 'source' => $order->source->value, 'status' => $order->status, 'clientOperationId' => $order->client_operation_id, 'waiter' => self::staff($order->waiter), 'createdAt' => $order->created_at?->toIso8601String(), 'cancelledAt' => $order->cancelled_at?->toIso8601String(), 'cancelReason' => $order->cancel_reason, 'items' => $order->items->map(fn ($item) => self::item($item))->values()->all(), 'printJobs' => $order->relationLoaded('printJobs') ? $order->printJobs->map(fn ($job) => ProductionResources::job($job))->values()->all() : []];
    }

    public static function session(TableSession $session, TableSessionService $service): array
    {
        return ['id' => $session->id, 'status' => $session->status, 'openedAt' => $session->opened_at->toIso8601String(), 'billRequestedAt' => $session->bill_requested_at?->toIso8601String(), 'billRequestSource' => $session->bill_request_source, 'table' => ['id' => $session->table->id, 'label' => $session->table->label, 'areaId' => $session->table->area_id, 'areaName' => $session->table->area->name], 'waiter' => self::staff($session->waiter), 'total' => DecimalMoney::fromMinor($service->totalMinor($session)), 'orders' => $session->orders->map(fn ($order) => self::order($order))->values()->all(), 'waiterHistory' => $session->waiterAssignments->map(fn ($entry) => ['id' => $entry->id, 'from' => $entry->fromWaiter ? self::staff($entry->fromWaiter) : null, 'to' => self::staff($entry->toWaiter), 'reason' => $entry->reason, 'createdAt' => $entry->created_at?->toIso8601String()])->values()->all(), 'tableHistory' => $session->tableTransfers->map(fn ($entry) => ['id' => $entry->id, 'fromTable' => ['id' => $entry->fromTable->id, 'label' => $entry->fromTable->label], 'toTable' => ['id' => $entry->toTable->id, 'label' => $entry->toTable->label], 'reason' => $entry->reason, 'createdAt' => $entry->created_at?->toIso8601String()])->values()->all()];
    }
}
