<?php

namespace App\Domain\Production\Http\Resources;

use App\Domain\Production\Models\Printer;
use App\Domain\Production\Models\PrintJob;
use App\Domain\Production\Models\ProductionCenter;

final class ProductionResources
{
    public static function printer(Printer $printer): array
    {
        return ['id' => $printer->id, 'name' => $printer->name, 'driverMode' => $printer->driver_mode, 'connectionType' => $printer->connection_type, 'systemName' => $printer->system_name, 'devicePath' => $printer->device_path, 'paperWidth' => $printer->paper_width, 'isActive' => $printer->is_active, 'isDefaultFallback' => $printer->is_default_fallback, 'isPreaccountDefault' => $printer->is_preaccount_default];
    }

    public static function center(ProductionCenter $center): array
    {
        return ['id' => $center->id, 'name' => $center->name, 'sortOrder' => $center->sort_order, 'isActive' => $center->is_active, 'productsCount' => (int) ($center->products_count ?? 0), 'printers' => $center->relationLoaded('printers') ? $center->printers->map(fn ($printer) => self::printer($printer) + ['priority' => $printer->pivot->priority])->values()->all() : []];
    }

    public static function job(PrintJob $job): array
    {
        return ['id' => $job->id, 'jobType' => $job->job_type, 'status' => $job->status, 'attempts' => $job->attempts, 'maxAttempts' => $job->max_attempts, 'errorCode' => $job->last_error_code, 'errorMessage' => $job->last_error_message, 'center' => $job->center ? ['id' => $job->center->id, 'name' => $job->center->name] : null, 'printer' => $job->printer ? self::printer($job->printer) : null, 'createdAt' => $job->created_at?->toIso8601String(), 'printedAt' => $job->printed_at?->toIso8601String()];
    }
}
