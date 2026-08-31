<?php

namespace App\Domain\Areas\Models;

use App\Domain\Tables\Models\RestaurantTable;
use Database\Factories\AreaFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    /** @use HasFactory<AreaFactory> */
    use HasFactory, HasUlids;

    protected $fillable = ['name', 'normalized_name', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer', 'is_active' => 'boolean'];
    }

    public function tables(): HasMany
    {
        return $this->hasMany(RestaurantTable::class)->orderBy('sort_order')->orderBy('id');
    }

    protected static function newFactory(): AreaFactory
    {
        return AreaFactory::new();
    }
}
