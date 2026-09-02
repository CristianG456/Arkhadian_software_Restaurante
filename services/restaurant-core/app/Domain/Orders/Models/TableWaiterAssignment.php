<?php

namespace App\Domain\Orders\Models;

use App\Domain\Staff\Models\StaffMember;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TableWaiterAssignment extends Model
{
    use HasUlids;

    protected $fillable = ['table_session_id', 'from_waiter_id', 'to_waiter_id', 'performed_by_actor_id', 'reason'];

    public function fromWaiter(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class, 'from_waiter_id');
    }

    public function toWaiter(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class, 'to_waiter_id');
    }
}
