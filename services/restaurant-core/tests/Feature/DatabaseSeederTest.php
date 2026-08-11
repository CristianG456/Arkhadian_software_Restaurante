<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    public function test_seeding_runs_cleanly_with_no_data_yet(): void
    {
        $exitCode = Artisan::call('db:seed', ['--force' => true]);

        $this->assertSame(0, $exitCode);
    }

    public function test_seeding_is_safe_to_run_more_than_once(): void
    {
        Artisan::call('db:seed', ['--force' => true]);
        $secondRun = Artisan::call('db:seed', ['--force' => true]);

        $this->assertSame(0, $secondRun);
    }
}
