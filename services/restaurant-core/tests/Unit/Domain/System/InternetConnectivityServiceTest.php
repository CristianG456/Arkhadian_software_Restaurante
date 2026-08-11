<?php

namespace Tests\Unit\Domain\System;

use App\Domain\System\Services\InternetConnectivityService;
use Tests\TestCase;

class InternetConnectivityServiceTest extends TestCase
{
    public function test_it_reports_offline_when_no_target_is_reachable(): void
    {
        // 192.0.2.0/24 is reserved for documentation (RFC 5737) and never
        // routable, so this deterministically fails without needing a real
        // outage or touching the network stack's DNS resolution.
        $service = new InternetConnectivityService(
            targets: [['host' => '192.0.2.1', 'port' => 53]],
            timeout: 0.3,
        );

        $this->assertSame(['status' => 'offline'], $service->check());
    }

    public function test_it_reports_online_when_at_least_one_target_is_reachable(): void
    {
        // The test suite already requires a live PostgreSQL connection
        // (see phpunit.xml), so its port is a reliable "something is
        // listening here" target without depending on real Internet access.
        $service = new InternetConnectivityService(
            targets: [
                ['host' => '192.0.2.1', 'port' => 53],
                ['host' => config('database.connections.pgsql.host'), 'port' => (int) config('database.connections.pgsql.port')],
            ],
            timeout: 0.3,
        );

        $this->assertSame(['status' => 'online'], $service->check());
    }
}
