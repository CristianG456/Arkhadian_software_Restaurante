<?php

namespace App\Domain\Mobile\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DevicePairing extends Model
{
    use HasUlids;

    protected $fillable = ['installation_id', 'code_hash', 'token_hash', 'max_uses', 'use_count', 'expires_at', 'used_at'];

    protected $hidden = ['code_hash', 'token_hash'];

    protected function casts(): array
    {
        return ['expires_at' => 'immutable_datetime', 'used_at' => 'immutable_datetime', 'max_uses' => 'integer', 'use_count' => 'integer'];
    }

    public function installation(): BelongsTo
    {
        return $this->belongsTo(RestaurantInstallation::class, 'installation_id');
    }
}
