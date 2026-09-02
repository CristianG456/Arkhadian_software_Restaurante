<?php

namespace App\Domain\Production\Models;

use Database\Factories\PrinterFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Printer extends Model
{
    use HasFactory, HasUlids;

    public const DRIVER_TEST_FILE = 'TEST_FILE';

    public const DRIVER_WINDOWS_RAW_SHARE = 'WINDOWS_RAW_SHARE';

    protected $fillable = ['name', 'normalized_name', 'driver_mode', 'connection_type', 'system_name', 'device_path', 'paper_width', 'is_active', 'is_default_fallback', 'is_preaccount_default'];

    protected function casts(): array
    {
        return ['paper_width' => 'integer', 'is_active' => 'boolean', 'is_default_fallback' => 'boolean', 'is_preaccount_default' => 'boolean'];
    }

    public function centers(): BelongsToMany
    {
        return $this->belongsToMany(ProductionCenter::class, 'production_center_printers')->withPivot(['id', 'priority', 'is_active'])->withTimestamps();
    }

    protected static function newFactory(): PrinterFactory
    {
        return PrinterFactory::new();
    }
}
