<?php

namespace App\Domain\Tables\Models;

use App\Domain\Areas\Models\Area;
use App\Domain\TableQr\Models\TableQrCredential;
use Database\Factories\RestaurantTableFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RestaurantTable extends Model
{
    /** @use HasFactory<RestaurantTableFactory> */
    use HasFactory, HasUlids;

    protected $fillable = ['area_id', 'label', 'normalized_label', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer', 'is_active' => 'boolean'];
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function qrCredentials(): HasMany
    {
        return $this->hasMany(TableQrCredential::class);
    }

    public function activeQrCredential(): HasOne
    {
        return $this->hasOne(TableQrCredential::class)
            ->where('status', TableQrCredential::STATUS_ACTIVE);
    }

    protected static function newFactory(): RestaurantTableFactory
    {
        return RestaurantTableFactory::new();
    }
}
