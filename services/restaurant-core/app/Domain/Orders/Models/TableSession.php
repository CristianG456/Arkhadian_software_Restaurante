<?php

namespace App\Domain\Orders\Models;

use App\Domain\Staff\Models\StaffMember;
use App\Domain\Tables\Models\RestaurantTable;
use Database\Factories\TableSessionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TableSession extends Model
{
    use HasFactory, HasUlids;

    public const STATUS_OPEN = 'OPEN';

    public const STATUS_CLOSED = 'CLOSED';

    protected $fillable = ['table_id', 'assigned_waiter_id', 'status', 'opened_at', 'closed_at', 'closed_reason', 'bill_requested_at', 'bill_request_source', 'opened_by_actor_id'];

    protected function casts(): array
    {
        return ['opened_at' => 'immutable_datetime', 'closed_at' => 'immutable_datetime', 'bill_requested_at' => 'immutable_datetime'];
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class);
    }

    public function waiter(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class, 'assigned_waiter_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->orderBy('created_at')->orderBy('id');
    }

    public function waiterAssignments(): HasMany
    {
        return $this->hasMany(TableWaiterAssignment::class)->orderBy('created_at');
    }

    public function tableTransfers(): HasMany
    {
        return $this->hasMany(TableSessionTableTransfer::class)->orderBy('created_at');
    }

    protected static function newFactory(): TableSessionFactory
    {
        return TableSessionFactory::new();
    }
}
