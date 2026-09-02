<?php

namespace App\Domain\Staff\Models;

use App\Domain\Orders\Models\TableSession;
use Database\Factories\StaffMemberFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffMember extends Model
{
    use HasFactory, HasUlids;

    public const TYPE_WAITER = 'WAITER';

    protected $fillable = ['name', 'normalized_name', 'staff_type', 'is_active', 'username', 'pin_hash'];

    protected $hidden = ['pin_hash'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function openSessions(): HasMany
    {
        return $this->hasMany(TableSession::class, 'assigned_waiter_id')->where('status', TableSession::STATUS_OPEN);
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(StaffPermission::class);
    }

    protected static function newFactory(): StaffMemberFactory
    {
        return StaffMemberFactory::new();
    }
}
