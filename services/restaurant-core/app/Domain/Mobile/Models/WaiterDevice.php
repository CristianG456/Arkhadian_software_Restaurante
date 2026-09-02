<?php

namespace App\Domain\Mobile\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WaiterDevice extends Model
{
    use HasUlids;

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_REVOKED = 'REVOKED';

    protected $fillable = ['installation_id', 'name', 'platform', 'device_identifier', 'credential_hash', 'status', 'paired_at', 'last_seen_at', 'revoked_at'];

    protected $hidden = ['credential_hash'];

    protected function casts(): array
    {
        return ['paired_at' => 'immutable_datetime', 'last_seen_at' => 'immutable_datetime', 'revoked_at' => 'immutable_datetime'];
    }

    public function installation(): BelongsTo
    {
        return $this->belongsTo(RestaurantInstallation::class, 'installation_id');
    }

    public function accessTokens(): HasMany
    {
        return $this->hasMany(WaiterAccessToken::class, 'device_id');
    }
}
