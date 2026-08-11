<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SystemStatusEndpointTest extends TestCase
{
    public function test_status_reports_a_healthy_system_when_the_database_is_reachable(): void
    {
        $response = $this->getJson('/api/system/status');

        $response->assertOk()->assertJson([
            'success' => true,
            'data' => [
                'core' => ['status' => 'online'],
                'database' => ['status' => 'connected'],
                'localServer' => ['status' => 'online'],
            ],
        ]);

        $response->assertJsonStructure([
            'success',
            'data' => [
                'core' => ['status', 'version'],
                'database' => ['status', 'latencyMs'],
                'localServer' => ['status', 'host', 'port'],
                'internet' => ['status'],
                'installationId',
                'checkedAt',
            ],
        ]);
    }

    public function test_status_reports_the_database_as_disconnected_instead_of_failing_the_request(): void
    {
        config(['database.connections.pgsql.password' => 'not-the-real-password']);
        DB::purge('pgsql');

        $response = $this->getJson('/api/system/status');

        $response->assertOk()->assertJson([
            'success' => true,
            'data' => [
                'core' => ['status' => 'online'],
                'database' => ['status' => 'disconnected', 'latencyMs' => null],
            ],
        ]);
    }

    public function test_internet_status_is_one_of_the_two_known_states(): void
    {
        $response = $this->getJson('/api/system/status');

        $response->assertOk();
        $this->assertContains(
            $response->json('data.internet.status'),
            ['online', 'offline'],
        );
    }
}
