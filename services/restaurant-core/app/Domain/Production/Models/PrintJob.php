<?php

namespace App\Domain\Production\Models;

use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\TableSession;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintJob extends Model
{
    use HasUlids;

    public const STATUS_PENDING = 'PENDING';

    public const STATUS_PROCESSING = 'PROCESSING';

    public const STATUS_PRINTED = 'PRINTED';

    public const STATUS_FAILED = 'FAILED';

    public const TYPE_ORDER = 'ORDER_TICKET';

    public const TYPE_REPRINT = 'ORDER_REPRINT';

    public const TYPE_CUSTOMER = 'CUSTOMER_REQUEST_TICKET';

    public const TYPE_TRANSFER = 'TABLE_TRANSFER_NOTICE';

    public const TYPE_CANCEL = 'ORDER_CANCEL_NOTICE';

    public const TYPE_PREACCOUNT = 'PREACCOUNT';

    public const TYPE_TEST = 'TEST_PRINT';

    protected $fillable = ['job_type', 'printer_id', 'production_center_id', 'order_id', 'table_session_id', 'idempotency_key', 'payload_snapshot', 'status', 'attempts', 'max_attempts', 'last_error_code', 'last_error_message', 'requested_by_actor_id', 'request_reason', 'available_at', 'processing_at', 'printed_at', 'failed_at'];

    protected function casts(): array
    {
        return ['payload_snapshot' => 'array', 'attempts' => 'integer', 'max_attempts' => 'integer', 'available_at' => 'immutable_datetime', 'processing_at' => 'immutable_datetime', 'printed_at' => 'immutable_datetime', 'failed_at' => 'immutable_datetime'];
    }

    public function printer(): BelongsTo
    {
        return $this->belongsTo(Printer::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(ProductionCenter::class, 'production_center_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TableSession::class, 'table_session_id');
    }
}
