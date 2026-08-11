<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    public function test_health_reports_liveness_without_touching_the_database(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk()->assertExactJson([
            'status' => 'ok',
            'service' => 'restaurant-core',
            'version' => config('system.version'),
        ]);
    }
}
