<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_check_returns_200_when_healthy(): void
    {
        $response = $this->getJson('/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'timestamp',
                'services' => [
                    'database' => ['status'],
                    'cache' => ['status'],
                ],
            ])
            ->assertJson([
                'status' => 'healthy',
                'services' => [
                    'database' => ['status' => 'up'],
                    'cache' => ['status' => 'up'],
                ],
            ]);
    }

    public function test_api_health_check_returns_200_when_healthy(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'healthy',
                'services' => [
                    'database' => ['status' => 'up'],
                    'cache' => ['status' => 'up'],
                ],
            ]);
    }

    public function test_health_check_returns_503_and_logs_error_when_database_fails(): void
    {
        Log::spy();
        DB::shouldReceive('select')
            ->once()
            ->andThrow(new \Exception('Database connection lost'));

        $response = $this->getJson('/health');

        $response->assertStatus(503)
            ->assertJson([
                'status' => 'unhealthy',
                'services' => [
                    'database' => [
                        'status' => 'down',
                        'reason' => 'Database connection lost',
                    ],
                    'cache' => [
                        'status' => 'up',
                    ],
                ],
            ]);

        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Health check failed') &&
                    isset($context['who']['user_id']) &&
                    isset($context['who']['ip']) &&
                    isset($context['what']['action']) &&
                    isset($context['why']['reason']);
            });
    }

    public function test_health_check_returns_503_and_logs_error_when_cache_fails(): void
    {
        Log::spy();
        Cache::shouldReceive('put')
            ->once()
            ->andThrow(new \Exception('Cache store unavailable'));

        $response = $this->getJson('/health');

        $response->assertStatus(503)
            ->assertJson([
                'status' => 'unhealthy',
                'services' => [
                    'database' => [
                        'status' => 'up',
                    ],
                    'cache' => [
                        'status' => 'down',
                        'reason' => 'Cache store unavailable',
                    ],
                ],
            ]);

        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Health check failed') &&
                    isset($context['who']['user_id']) &&
                    isset($context['what']['action']) &&
                    isset($context['why']['failures']['cache']);
            });
    }
}
