<?php

namespace App\Domain\Mobile\Services;

use App\Domain\Mobile\Models\WaiterNotification;
use App\Domain\Orders\Enums\OrderSource;
use App\Domain\Orders\Events\TableWaiterTransferred;
use App\Domain\Orders\Models\Order;

final class MobileNotificationService
{
    public function orderConfirmed(string $orderId): void
    {
        $order = Order::query()->with('session.table.area')->find($orderId);
        if (! $order || $order->source !== OrderSource::CUSTOMER_QR) {
            return;
        }
        if (WaiterNotification::query()->where('recipient_staff_id', $order->responsible_waiter_id)->where('type', 'CUSTOMER_ORDER')->where('payload->orderId', $order->id)->exists()) {
            return;
        }
        WaiterNotification::query()->create(['recipient_staff_id' => $order->responsible_waiter_id, 'type' => 'CUSTOMER_ORDER', 'payload' => ['orderId' => $order->id, 'orderNumber' => $order->order_number, 'sessionId' => $order->table_session_id, 'areaName' => $order->session->table->area->name, 'tableLabel' => $order->session->table->label, 'message' => 'Nueva solicitud del cliente']]);
    }

    public function transferred(TableWaiterTransferred $event): void
    {
        WaiterNotification::query()->create(['recipient_staff_id' => $event->toWaiterId, 'type' => 'TABLE_TRANSFERRED', 'payload' => ['sessionId' => $event->sessionId, 'areaName' => $event->areaName, 'tableLabel' => $event->tableLabel, 'fromWaiterName' => $event->fromWaiterName, 'message' => 'Te asignaron '.$event->areaName.' · '.$event->tableLabel]]);
    }
}
