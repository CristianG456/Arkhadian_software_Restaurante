<?php

namespace App\Domain\Production\Printing;

use App\Domain\Production\Models\Printer;
use App\Domain\Production\Models\PrintJob;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class TestFilePrintDriver implements PrintDriver
{
    public function supports(Printer $printer): bool
    {
        return $printer->driver_mode === Printer::DRIVER_TEST_FILE;
    }

    public function send(Printer $printer, PrintJob $job, string $document): void
    {
        if ($printer->device_path === 'fail://always') {
            throw new RuntimeException('TEST_DRIVER_FAILURE');
        }
        Storage::disk('local')->append('printing/'.$printer->id.'.log', $document."\n");
    }
}
