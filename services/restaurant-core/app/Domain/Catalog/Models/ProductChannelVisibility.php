<?php

namespace App\Domain\Catalog\Models;

use App\Domain\Catalog\Enums\ProductChannel;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductChannelVisibility extends Model
{
    use HasUlids;

    protected $fillable = ['product_id', 'channel', 'is_visible'];

    protected function casts(): array
    {
        return ['channel' => ProductChannel::class, 'is_visible' => 'boolean'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
