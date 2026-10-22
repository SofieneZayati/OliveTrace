<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class DeploymentHealthTest extends TestCase
{
    public function test_health_check_passes_when_the_database_is_available(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_health_check_fails_without_exposing_database_details(): void
    {
        config(['app.debug' => false]);
        DB::shouldReceive('select')->once()->with('SELECT 1')
            ->andThrow(new RuntimeException('private-database-connection-detail'));
        $this->get('/up')->assertStatus(500)->assertDontSee('private-database-connection-detail');
    }

    public function test_configured_proxy_preserves_https_links_and_secure_cookies(): void
    {
        config(['trustedproxy.proxies' => '*', 'session.secure' => true]);
        $response = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])
            ->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-Port' => '443'])->get('/login');
        $response->assertOk()->assertSee('https://localhost/login', false);
        $cookies = $response->headers->getCookies();
        $this->assertNotEmpty($cookies);
        foreach ($cookies as $cookie) {
            $this->assertTrue($cookie->isSecure());
        }
    }
}
