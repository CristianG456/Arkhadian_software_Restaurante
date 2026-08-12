<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiErrorEnvelopeTest extends TestCase
{
    public function test_unknown_api_routes_return_the_standard_error_envelope(): void
    {
        $response = $this->getJson('/api/this-route-does-not-exist');

        $response->assertStatus(404)->assertExactJson([
            'success' => false,
            'error' => [
                'code' => 'NOT_FOUND',
                'message' => 'The requested endpoint does not exist.',
            ],
        ]);
    }

    public function test_wrong_http_method_returns_the_standard_error_envelope(): void
    {
        $response = $this->postJson('/api/health');

        $response->assertStatus(405)->assertJsonStructure([
            'success',
            'error' => ['code', 'message'],
        ]);

        $response->assertJson([
            'success' => false,
            'error' => ['code' => 'METHOD_NOT_ALLOWED'],
        ]);
    }

    public function test_error_responses_never_leak_raw_exception_details(): void
    {
        config(['app.debug' => false]);

        $response = $this->getJson('/api/this-route-does-not-exist');

        $response->assertJsonMissingPath('error.trace')
            ->assertJsonMissingPath('exception');
    }
}
