<?php

namespace App\Domain\Production\Printing;

use App\Domain\Production\Models\Printer;
use App\Domain\Production\Models\PrintJob;

interface PrintDriver
{
    public function supports(Printer $printer): bool;

    public function send(Printer $printer, PrintJob $job, string $document): void;
}
