<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class HardeningTest extends TestCase
{
    public function test_api_envia_headers_de_seguranca_e_csp(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'none'", (string) $response->headers->get('Content-Security-Policy'));
    }

    public function test_cors_production_sem_frontend_url_nao_libera_origem(): void
    {
        $prevEnv = env('APP_ENV');
        $prevUrl = env('FRONTEND_URL');

        try {
            putenv('APP_ENV=production');
            putenv('FRONTEND_URL');
            $_ENV['APP_ENV'] = 'production';
            $_ENV['FRONTEND_URL'] = '';
            $_SERVER['APP_ENV'] = 'production';
            $_SERVER['FRONTEND_URL'] = '';

            $config = require base_path('config/cors.php');
            $this->assertSame([], $config['allowed_origins']);
        } finally {
            if ($prevEnv === false) {
                putenv('APP_ENV');
                unset($_ENV['APP_ENV'], $_SERVER['APP_ENV']);
            } else {
                putenv('APP_ENV='.$prevEnv);
                $_ENV['APP_ENV'] = $prevEnv;
                $_SERVER['APP_ENV'] = $prevEnv;
            }
            if ($prevUrl === false || $prevUrl === null) {
                putenv('FRONTEND_URL');
                unset($_ENV['FRONTEND_URL'], $_SERVER['FRONTEND_URL']);
            } else {
                putenv('FRONTEND_URL='.$prevUrl);
                $_ENV['FRONTEND_URL'] = $prevUrl;
                $_SERVER['FRONTEND_URL'] = $prevUrl;
            }
        }
    }

    public function test_login_respeita_throttle_quando_limiter_apertado(): void
    {
        RateLimiter::for('login', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(2)->by('hardening-test'));

        $payload = [
            'email' => 'naoexiste-hardening@local.test',
            'password' => 'errada',
        ];

        $this->postJson('/api/v1/auth/login', $payload)->assertStatus(422);
        $this->postJson('/api/v1/auth/login', $payload)->assertStatus(422);
        $this->postJson('/api/v1/auth/login', $payload)->assertStatus(429);
    }
}
