<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthTest extends TestCase
{
    /**
     * Test GET /health returns 200 OK and valid JSON structure.
     */
    public function test_health_check_returns_ok(): void
    {
        $response = $this->getJson('/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
                'service' => 'order-service',
            ]);
    }
}
