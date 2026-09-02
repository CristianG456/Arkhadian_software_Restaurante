<?php

namespace App\Domain\Production\Services;

use App\Domain\Production\Models\PrintJob;
use App\Domain\Production\Printing\PrintDocumentRenderer;
use App\Domain\Production\Printing\PrintDriver;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

final class PrintJobProcessor
{
    public function __construct(private readonly iterable $drivers, private readonly PrintDocumentRenderer $renderer) {}

    public function processNext(): ?PrintJob
    {
        $job = DB::transaction(function () {
            $job = PrintJob::query()->where('status', PrintJob::STATUS_PENDING)->where(fn ($q) => $q->whereNull('available_at')->orWhere('available_at', '<=', now()))->orderBy('created_at')->lock('for update skip locked')->first();
            if ($job) {
                $job->update(['status' => PrintJob::STATUS_PROCESSING, 'processing_at' => now(), 'attempts' => $job->attempts + 1]);
            }

            return $job;
        });
        if (! $job) {
            return null;
        }
        try {
            $job->load('printer');
            if (! $job->printer || ! $job->printer->is_active) {
                throw new RuntimeException('PRINTER_NOT_CONFIGURED');
            }
            $driver = collect($this->drivers)->first(fn (PrintDriver $driver) => $driver->supports($job->printer));
            if (! $driver) {
                throw new RuntimeException('DRIVER_NOT_SUPPORTED');
            }
            $driver->send($job->printer, $job, $this->renderer->render($job));
            $job->update(['status' => PrintJob::STATUS_PRINTED, 'printed_at' => now(), 'failed_at' => null, 'last_error_code' => null, 'last_error_message' => null]);
        } catch (Throwable $error) {
            $failed = $job->attempts >= $job->max_attempts;
            $job->update(['status' => $failed ? PrintJob::STATUS_FAILED : PrintJob::STATUS_PENDING, 'available_at' => $failed ? null : now()->addSeconds(5), 'failed_at' => $failed ? now() : null, 'last_error_code' => $error->getMessage(), 'last_error_message' => 'No pudimos enviar el documento a la impresora configurada.']);
            report($error);
        }

        return $job->refresh();
    }

    public function retry(PrintJob $job): PrintJob
    {
        $job->update(['status' => PrintJob::STATUS_PENDING, 'attempts' => 0, 'available_at' => now(), 'failed_at' => null]);

        return $job->refresh();
    }
}
