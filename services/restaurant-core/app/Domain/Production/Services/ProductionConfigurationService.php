<?php

namespace App\Domain\Production\Services;

use App\Domain\Production\Models\Printer;
use App\Domain\Production\Models\PrintJob;
use App\Domain\Production\Models\ProductionCenter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ProductionConfigurationService
{
    public function centers(): Collection
    {
        return ProductionCenter::query()->with('printers')->withCount('products')->orderBy('sort_order')->get();
    }

    public function printers(): Collection
    {
        return Printer::query()->with('centers')->orderBy('name')->get();
    }

    public function createCenter(array $data): ProductionCenter
    {
        return ProductionCenter::query()->create(['name' => $data['name'], 'normalized_name' => $data['normalizedName'], 'sort_order' => ((int) ProductionCenter::query()->max('sort_order')) + 1, 'is_active' => $data['isActive'] ?? true]);
    }

    public function updateCenter(ProductionCenter $center, array $data): ProductionCenter
    {
        $center->update(array_filter(['name' => $data['name'] ?? null, 'normalized_name' => $data['normalizedName'] ?? null, 'is_active' => $data['isActive'] ?? null], fn ($value) => $value !== null));

        return $center->refresh();
    }

    public function assign(ProductionCenter $center, string $printerId, int $priority): ProductionCenter
    {
        DB::transaction(function () use ($center, $printerId, $priority) {
            $center->printers()->wherePivot('priority', $priority)->detach();
            $center->printers()->syncWithoutDetaching([$printerId => ['id' => (string) Str::ulid(), 'priority' => $priority, 'is_active' => true]]);
        });

        return $center->refresh()->load('printers');
    }

    public function createPrinter(array $data): Printer
    {
        return DB::transaction(function () use ($data) {
            if ($data['isPreaccountDefault'] ?? false) {
                Printer::query()->update(['is_preaccount_default' => false]);
            }

return Printer::query()->create($this->printerData($data));
        });
    }

    public function updatePrinter(Printer $printer, array $data): Printer
    {
        return DB::transaction(function () use ($printer, $data) {
            if ($data['isPreaccountDefault'] ?? false) {
                Printer::query()->whereKeyNot($printer->id)->update(['is_preaccount_default' => false]);
            } $printer->update($this->printerData($data, true));

            return $printer->refresh();
        });
    }

    public function test(Printer $printer): PrintJob
    {
        return PrintJob::query()->create(['job_type' => PrintJob::TYPE_TEST, 'printer_id' => $printer->id, 'idempotency_key' => 'test:'.$printer->id.':'.Str::ulid(), 'payload_snapshot' => ['location' => 'Impresora: '.$printer->name, 'createdAt' => now()->toDateTimeString()], 'status' => PrintJob::STATUS_PENDING, 'attempts' => 0, 'max_attempts' => 1, 'available_at' => now()]);
    }

    private function printerData(array $data, bool $partial = false): array
    {
        $map = ['name' => 'name', 'normalizedName' => 'normalized_name', 'driverMode' => 'driver_mode', 'connectionType' => 'connection_type', 'systemName' => 'system_name', 'devicePath' => 'device_path', 'paperWidth' => 'paper_width', 'isActive' => 'is_active', 'isDefaultFallback' => 'is_default_fallback', 'isPreaccountDefault' => 'is_preaccount_default'];
        $result = [];
        foreach ($map as $input => $column) {
            if (array_key_exists($input, $data)) {
                $result[$column] = $data[$input];
            }
        }

return $result + ($partial ? [] : ['paper_width' => 80, 'is_active' => true, 'is_default_fallback' => false, 'is_preaccount_default' => false]);
    }
}
