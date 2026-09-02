<?php

namespace App\Domain\Mobile\Models;

use App\Domain\Staff\Models\StaffMember;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaiterAccessToken extends Model
{
    use HasUlids;

    protected $fillable = ['staff_member_id', 'device_id', 'token_hash', 'expires_at', 'last_used_at', 'revoked_at'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['expires_at' => 'immutable_datetime', 'last_used_at' => 'immutable_datetime', 'revoked_at' => 'immutable_datetime'];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class, 'staff_member_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(WaiterDevice::class, 'device_id');
    }
}
