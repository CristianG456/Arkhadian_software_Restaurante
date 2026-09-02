<?php

namespace App\Domain\Production\Printing;

use App\Domain\Production\Models\Printer;
use App\Domain\Production\Models\PrintJob;
use RuntimeException;

class WindowsRawSharePrintDriver implements PrintDriver
{
    public function supports(Printer $printer): bool
    {
        return $printer->driver_mode === Printer::DRIVER_WINDOWS_RAW_SHARE;
    }

    public function send(Printer $printer, PrintJob $job, string $document): void
    {
        $name = (string) $printer->system_name;
        if (PHP_OS_FAMILY !== 'Windows') {
            throw new RuntimeException('WINDOWS_REQUIRED');
        }
        if (! preg_match('/^[A-Za-z0-9 _.-]{1,100}$/', $name)) {
            throw new RuntimeException('INVALID_PRINTER_SHARE');
        }
        $handle = @fopen('\\\\localhost\\'.$name, 'wb');
        if (! is_resource($handle)) {
            throw new RuntimeException('PRINTER_UNAVAILABLE');
        }
        try {
            if (fwrite($handle, $document."\f") === false) {
                throw new RuntimeException('SPOOL_WRITE_FAILED');
            }
        } finally {
            fclose($handle);
        }
    }
}
