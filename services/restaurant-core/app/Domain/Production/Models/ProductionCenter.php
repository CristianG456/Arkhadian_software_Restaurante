<?php

namespace App\Domain\Production\Models;

use App\Domain\Catalog\Models\Product;
use Database\Factories\ProductionCenterFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionCenter extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = ['name', 'normalized_name', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer', 'is_active' => 'boolean'];
    }

    public function printers(): BelongsToMany
    {
        return $this->belongsToMany(Printer::class, 'production_center_printers')->withPivot(['id', 'priority', 'is_active'])->withTimestamps()->orderByPivot('priority');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    protected static function newFactory(): ProductionCenterFactory
    {
        return ProductionCenterFactory::new();
    }
}
