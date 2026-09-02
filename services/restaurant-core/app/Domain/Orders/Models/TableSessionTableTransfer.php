<?php

namespace App\Domain\Orders\Models;

use App\Domain\Tables\Models\RestaurantTable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TableSessionTableTransfer extends Model
{
    use HasUlids;

    protected $fillable = ['table_session_id', 'from_table_id', 'to_table_id', 'performed_by_actor_id', 'reason'];

    public function fromTable(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'from_table_id');
    }

    public function toTable(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'to_table_id');
    }
}
