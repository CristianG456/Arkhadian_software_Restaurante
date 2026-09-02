<?php

namespace App\Domain\Orders\Models;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductVariant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    use HasUlids;

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = ['order_id', 'product_id', 'variant_id', 'quantity', 'product_name_snapshot', 'variant_name_snapshot', 'unit_price_snapshot', 'line_base_total_snapshot', 'notes', 'status', 'cancelled_at', 'cancelled_by_actor_id', 'cancel_reason'];

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'unit_price_snapshot' => 'decimal:2', 'line_base_total_snapshot' => 'decimal:2', 'cancelled_at' => 'immutable_datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function modifiers(): HasMany
    {
        return $this->hasMany(OrderItemModifier::class);
    }
}
