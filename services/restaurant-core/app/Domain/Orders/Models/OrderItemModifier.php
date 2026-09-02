<?php

namespace App\Domain\Orders\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemModifier extends Model
{
    use HasUlids;

    protected $fillable = ['order_item_id', 'modifier_group_id', 'modifier_option_id', 'group_name_snapshot', 'option_name_snapshot', 'price_delta_snapshot'];

    protected function casts(): array
    {
        return ['price_delta_snapshot' => 'decimal:2'];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }
}
