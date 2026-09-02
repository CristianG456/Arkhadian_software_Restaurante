<?php

namespace App\Domain\Production\Services;

use App\Domain\Orders\Enums\OrderSource;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Orders\Models\TableSession;
use App\Domain\Orders\Support\DecimalMoney;
use App\Domain\Production\Models\PrintJob;
use App\Domain\Production\Models\ProductionCenter;
use Illuminate\Support\Str;

final class ProductionRoutingService
{
    public function routeOrder(string $orderId): void
    {
        $order = Order::query()->with(['waiter', 'session.table.area', 'items.product.productionCenter.printers', 'items.variant.productionCenter.printers', 'items.modifiers'])->findOrFail($orderId);
        $groups = $order->items->where('status', OrderItem::STATUS_ACTIVE)->filter(fn ($item) => $item->product->requires_production)->groupBy(fn ($item) => $item->variant->production_center_id ?: $item->product->production_center_id ?: 'UNROUTED');
        foreach ($groups as $centerId => $items) {
            $center = $centerId === 'UNROUTED' ? null : ProductionCenter::query()->with('printers')->find($centerId);
            $printer = $center?->printers->first(fn ($printer) => $printer->pivot->is_active && $printer->is_active);
            $type = $order->source === OrderSource::CUSTOMER_QR ? PrintJob::TYPE_CUSTOMER : PrintJob::TYPE_ORDER;
            $job = PrintJob::query()->firstOrCreate(['idempotency_key' => "order:{$order->id}:center:{$centerId}:v1"], [
                'job_type' => $type, 'printer_id' => $printer?->id, 'production_center_id' => $center?->id, 'order_id' => $order->id, 'table_session_id' => $order->table_session_id,
                'payload_snapshot' => $this->orderSnapshot($order, $items->values()->all()), 'status' => $printer ? PrintJob::STATUS_PENDING : PrintJob::STATUS_FAILED,
                'attempts' => 0, 'max_attempts' => 3, 'last_error_code' => $center ? 'PRINTER_NOT_CONFIGURED' : 'PRODUCT_CENTER_NOT_CONFIGURED', 'last_error_message' => $center ? 'No hay una impresora activa configurada para '.$center->name.'.' : 'Hay productos sin centro de producción.', 'available_at' => now(),
            ]);
            if (! $printer && ! $job->failed_at) {
                $job->update(['failed_at' => now()]);
            }
        }
    }

    public function reprint(Order $order, ?string $centerId, ?string $reason): array
    {
        $originals = PrintJob::query()->where('order_id', $order->id)->whereIn('job_type', [PrintJob::TYPE_ORDER, PrintJob::TYPE_CUSTOMER])->when($centerId, fn ($q) => $q->where('production_center_id', $centerId))->get();

        return $originals->map(function (PrintJob $original) use ($reason) {
            return PrintJob::query()->create([
                'job_type' => PrintJob::TYPE_REPRINT, 'printer_id' => $original->printer_id, 'production_center_id' => $original->production_center_id, 'order_id' => $original->order_id, 'table_session_id' => $original->table_session_id,
                'idempotency_key' => 'reprint:'.$original->id.':'.Str::ulid(), 'payload_snapshot' => $original->payload_snapshot, 'status' => $original->printer_id ? PrintJob::STATUS_PENDING : PrintJob::STATUS_FAILED,
                'attempts' => 0, 'max_attempts' => 3, 'request_reason' => $reason, 'available_at' => now(),
            ]);
        })->all();
    }

    public function cancelItem(string $itemId): void
    {
        $item = OrderItem::query()->with(['order.session.table.area', 'order.waiter', 'product.productionCenter.printers', 'variant.productionCenter.printers', 'modifiers'])->findOrFail($itemId);
        $center = $item->variant->productionCenter ?: $item->product->productionCenter;
        if (! $center || ! PrintJob::query()->where('order_id', $item->order_id)->where('production_center_id', $center->id)->where('status', PrintJob::STATUS_PRINTED)->exists()) {
            return;
        }
        $printer = $center->printers->first(fn ($printer) => $printer->pivot->is_active && $printer->is_active);
        PrintJob::query()->firstOrCreate(['idempotency_key' => 'cancel:item:'.$item->id], [
            'job_type' => PrintJob::TYPE_CANCEL, 'printer_id' => $printer?->id, 'production_center_id' => $center->id, 'order_id' => $item->order_id, 'table_session_id' => $item->order->table_session_id,
            'payload_snapshot' => $this->orderSnapshot($item->order, [$item]) + ['reason' => $item->cancel_reason], 'status' => $printer ? PrintJob::STATUS_PENDING : PrintJob::STATUS_FAILED, 'attempts' => 0, 'max_attempts' => 3, 'available_at' => now(),
        ]);
    }

    public function cancelOrder(string $orderId): void
    {
        OrderItem::query()->where('order_id', $orderId)->pluck('id')->each(fn (string $itemId) => $this->cancelItem($itemId));
    }

    public function tableMoved(string $sessionId, string $fromTableId): void
    {
        $session = TableSession::query()->with(['table.area', 'waiter'])->findOrFail($sessionId);
        $centerIds = PrintJob::query()->where('table_session_id', $sessionId)->whereNotNull('production_center_id')->distinct()->pluck('production_center_id');
        foreach (ProductionCenter::query()->with('printers')->whereIn('id', $centerIds)->get() as $center) {
            $printer = $center->printers->first(fn ($printer) => $printer->pivot->is_active && $printer->is_active);
            PrintJob::query()->firstOrCreate(['idempotency_key' => 'move:'.$sessionId.':'.$fromTableId.':'.$session->table_id.':'.$center->id], [
                'job_type' => PrintJob::TYPE_TRANSFER, 'printer_id' => $printer?->id, 'production_center_id' => $center->id, 'table_session_id' => $sessionId,
                'payload_snapshot' => ['location' => 'Mesa anterior → '.$session->table->area->name.' · '.$session->table->label, 'waiter' => 'Mesero: '.$session->waiter->name, 'createdAt' => now()->format('H:i')], 'status' => $printer ? PrintJob::STATUS_PENDING : PrintJob::STATUS_FAILED, 'attempts' => 0, 'max_attempts' => 3, 'available_at' => now(),
            ]);
        }
    }

    private function orderSnapshot(Order $order, array $items): array
    {
        return ['location' => $order->session->table->area->name.' · '.$order->session->table->label, 'orderLabel' => 'Pedido #'.$order->order_number, 'waiter' => 'Mesero: '.$order->waiter->name, 'source' => $order->source->value, 'customerRequest' => $order->source === OrderSource::CUSTOMER_QR, 'createdAt' => $order->created_at->format('H:i'), 'items' => collect($items)->map(fn ($item) => ['quantity' => $item->quantity, 'name' => $item->product_name_snapshot, 'variant' => $item->variant_name_snapshot, 'modifiers' => $item->modifiers->pluck('option_name_snapshot')->all(), 'notes' => $item->notes, 'lineTotal' => DecimalMoney::fromMinor((DecimalMoney::toMinor((string) $item->unit_price_snapshot) + $item->modifiers->sum(fn ($m) => DecimalMoney::toMinor((string) $m->price_delta_snapshot))) * $item->quantity)])->all()];
    }
}
