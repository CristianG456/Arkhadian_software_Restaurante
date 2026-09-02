<?php

namespace App\Domain\Orders\Models;

use App\Domain\Orders\Enums\OrderSource;
use App\Domain\Production\Models\PrintJob;
use App\Domain\Staff\Models\StaffMember;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory, HasUlids;

    public const STATUS_CONFIRMED = 'CONFIRMED';

    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = ['table_session_id', 'responsible_waiter_id', 'source', 'status', 'client_operation_id', 'created_by_actor_id', 'cancelled_at', 'cancel_reason'];

    protected function casts(): array
    {
        return ['source' => OrderSource::class, 'order_number' => 'integer', 'cancelled_at' => 'immutable_datetime'];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TableSession::class, 'table_session_id');
    }

    public function waiter(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class, 'responsible_waiter_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('created_at')->orderBy('id');
    }

    public function printJobs(): HasMany
    {
        return $this->hasMany(PrintJob::class);
    }

    protected static function newFactory(): OrderFactory
    {
        return OrderFactory::new();
    }
}
