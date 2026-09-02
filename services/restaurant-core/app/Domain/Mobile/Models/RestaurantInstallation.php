<?php

namespace App\Domain\Mobile\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RestaurantInstallation extends Model
{
    use HasUuids;

    protected $fillable = ['id', 'restaurant_code', 'restaurant_name', 'operating_mode', 'primary_color'];

    public function devices(): HasMany
    {
        return $this->hasMany(WaiterDevice::class, 'installation_id');
    }

    public function pairings(): HasMany
    {
        return $this->hasMany(DevicePairing::class, 'installation_id');
    }
}
