<?php

namespace Tests\Feature;

use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Prometheus /metrics endpoint: token-gated, 404 when disabled.
 */
class MetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_disabled_without_a_token(): void
    {
        config(['rustdesk.metrics_token' => '']);

        $this->get('/metrics')->assertNotFound();
    }

    public function test_requires_the_bearer_token(): void
    {
        config(['rustdesk.metrics_token' => 's3cret']);

        $this->get('/metrics')->assertStatus(401);
        $this->withHeader('Authorization', 'Bearer wrong')->get('/metrics')->assertStatus(401);
    }

    public function test_exposes_metrics_with_a_valid_token(): void
    {
        config(['rustdesk.metrics_token' => 's3cret']);
        Device::create(['rustdesk_id' => 'd1', 'uuid' => 'u1', 'is_online' => true]);
        Device::create(['rustdesk_id' => 'd2', 'uuid' => 'u2', 'is_online' => false]);

        $res = $this->withHeader('Authorization', 'Bearer s3cret')->get('/metrics');

        $res->assertOk();
        $this->assertStringContainsString('text/plain', (string) $res->headers->get('Content-Type'));
        $body = $res->getContent();
        $this->assertStringContainsString('rustdesk_up 1', $body);
        $this->assertStringContainsString('rustdesk_devices_total 2', $body);
        $this->assertStringContainsString('rustdesk_devices_online 1', $body);
        $this->assertStringContainsString('# TYPE rustdesk_devices_total gauge', $body);
    }
}
