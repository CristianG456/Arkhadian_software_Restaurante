<?php

namespace App\Domain\Catalog\Models;

use Database\Factories\ModifierOptionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModifierOption extends Model
{
    /** @use HasFactory<ModifierOptionFactory> */
    use HasFactory, HasUlids;

    protected $fillable = [
        'modifier_group_id',
        'name',
        'normalized_name',
        'price_delta',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_delta' => 'decimal:2',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ModifierGroup::class, 'modifier_group_id');
    }

    protected static function newFactory(): ModifierOptionFactory
    {
        return ModifierOptionFactory::new();
    }
}
