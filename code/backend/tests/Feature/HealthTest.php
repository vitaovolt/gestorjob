<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_endpoint_returns_ok_envelope(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'ok')
            ->assertJsonPath('data.service', 'gestor-job-api')
            ->assertJsonPath('data.version', 'v1')
            ->assertJsonPath('data.checks.database', 'ok')
            ->assertJsonStructure([
                'success',
                'data' => ['service', 'version', 'status', 'checks'],
                'message',
                'errors',
            ]);

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
    }
}
