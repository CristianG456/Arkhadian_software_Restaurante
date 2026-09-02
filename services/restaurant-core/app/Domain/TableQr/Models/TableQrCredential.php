<?php

namespace App\Domain\TableQr\Models;

use App\Domain\Tables\Models\RestaurantTable;
use Database\Factories\TableQrCredentialFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TableQrCredential extends Model
{
    /** @use HasFactory<TableQrCredentialFactory> */
    use HasFactory, HasUlids;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'restaurant_table_id',
        'public_token',
        'version',
        'status',
        'revoked_at',
    ];

    protected $hidden = ['public_token'];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'revoked_at' => 'immutable_datetime',
        ];
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'restaurant_table_id');
    }

    protected static function newFactory(): TableQrCredentialFactory
    {
        return TableQrCredentialFactory::new();
    }
}
