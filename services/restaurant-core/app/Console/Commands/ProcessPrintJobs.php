<?php

namespace App\Console\Commands;

use App\Domain\Production\Services\PrintJobProcessor;
use Illuminate\Console\Command;

class ProcessPrintJobs extends Command
{
    protected $signature = 'production:work {--once : Process at most one available job} {--sleep=2 : Seconds between checks}';

    protected $description = 'Process persistent local print jobs';

    public function handle(PrintJobProcessor $processor): int
    {
        do {
            $job = $processor->processNext();
            if ($this->option('once')) {
                return self::SUCCESS;
            }
            if (! $job) {
                sleep(max(1, (int) $this->option('sleep')));
            }
        } while (true);
    }
}
