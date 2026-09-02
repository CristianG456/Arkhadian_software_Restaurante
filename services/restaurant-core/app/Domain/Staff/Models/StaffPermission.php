<?php

namespace App\Domain\Staff\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffPermission extends Model
{
    use HasUlids;

    protected $fillable = ['staff_member_id', 'permission', 'is_granted'];

    protected function casts(): array
    {
        return ['is_granted' => 'boolean'];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class, 'staff_member_id');
    }
}
