<?php

namespace App\Domain\Mobile\Models;

use App\Domain\Staff\Models\StaffMember;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaiterNotification extends Model
{
    use HasUlids;

    protected $fillable = ['recipient_staff_id', 'type', 'payload', 'read_at'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'read_at' => 'immutable_datetime'];
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class, 'recipient_staff_id');
    }
}
