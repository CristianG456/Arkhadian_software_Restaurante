<?php

namespace App\Domain\Tables\Models;

use App\Domain\Areas\Models\Area;
use Database\Factories\RestaurantTableFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    protected static function newFactory(): RestaurantTableFactory
    {
        return RestaurantTableFactory::new();
    }
}
