<?php

namespace App\Domain\Production\Services;

use App\Domain\Orders\Enums\OrderSource;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Orders\Models\TableSession;
use App\Domain\Orders\Services\TableSessionService;
use App\Domain\Orders\Support\DecimalMoney;
use App\Domain\Production\Models\Printer;
use App\Domain\Production\Models\PrintJob;
use App\Support\Http\ApiException;
use Illuminate\Support\Str;

final class PreaccountService
{
    public function __construct(private readonly TableSessionService $sessions) {}

    public function preview(TableSession $session): array
    {
        $session = $this->sessions->hydrate($session);
        $items = $session->orders->where('status', 'CONFIRMED')->flatMap->items->where('status', OrderItem::STATUS_ACTIVE);

        return ['restaurantName' => config('app.name'), 'location' => $session->table->area->name.' · '.$session->table->label, 'waiter' => 'Mesero: '.$session->waiter->name, 'createdAt' => now()->format('Y-m-d H:i'), 'items' => $items->map(fn ($item) => ['quantity' => $item->quantity, 'name' => $item->product_name_snapshot, 'variant' => $item->variant_name_snapshot, 'modifiers' => $item->modifiers->pluck('option_name_snapshot')->all(), 'lineTotal' => DecimalMoney::fromMinor((DecimalMoney::toMinor((string) $item->unit_price_snapshot) + $item->modifiers->sum(fn ($m) => DecimalMoney::toMinor((string) $m->price_delta_snapshot))) * $item->quantity)])->values()->all(), 'total' => DecimalMoney::fromMinor($this->sessions->totalMinor($session)), 'notice' => 'PRECUENTA · NO ES FACTURA'];
    }

    public function print(TableSession $session): PrintJob
    {
        if ($session->status !== TableSession::STATUS_OPEN) {
            throw new ApiException('SESSION_NOT_OPEN', 'La atención ya no está abierta.', 409);
        }
        $printer = Printer::query()->where('is_preaccount_default', true)->where('is_active', true)->first();

        return PrintJob::query()->create(['job_type' => PrintJob::TYPE_PREACCOUNT, 'printer_id' => $printer?->id, 'table_session_id' => $session->id, 'idempotency_key' => 'preaccount:'.$session->id.':'.Str::ulid(), 'payload_snapshot' => $this->preview($session), 'status' => $printer ? PrintJob::STATUS_PENDING : PrintJob::STATUS_FAILED, 'attempts' => 0, 'max_attempts' => 3, 'last_error_code' => $printer ? null : 'PREACCOUNT_PRINTER_NOT_CONFIGURED', 'last_error_message' => $printer ? null : 'No hay impresora de precuenta configurada.', 'available_at' => now(), 'failed_at' => $printer ? null : now()]);
    }

    public function requestBill(TableSession $session, OrderSource $source): TableSession
    {
        if ($session->status !== TableSession::STATUS_OPEN) {
            throw new ApiException('SESSION_NOT_OPEN', 'La atención ya no está abierta.', 409);
        }
        $session->update(['bill_requested_at' => now(), 'bill_request_source' => $source->value]);

        return $session->refresh();
    }
}
