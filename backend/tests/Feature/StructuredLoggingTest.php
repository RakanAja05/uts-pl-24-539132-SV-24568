<?php

namespace Tests\Feature;

use App\Models\Plant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StructuredLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_logging_info_when_plant_created(): void
    {
        Log::spy();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $payload = [
            'name' => 'Monstera Deliciosa',
            'species' => 'Monstera',
            'watering_interval_days' => 7,
            'notes' => 'Place in indirect sunlight',
        ];

        $response = $this->postJson('/api/plants', $payload);
        $response->assertStatus(201);

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(function ($message, $context) use ($user) {
                return $message === 'Plant created successfully' &&
                    $context['who']['user_id'] === $user->id &&
                    $context['what']['action'] === 'store_plant' &&
                    isset($context['why']['plant_id']) &&
                    $context['why']['name'] === 'Monstera Deliciosa';
            });
    }

    public function test_logging_warning_when_plant_validation_fails(): void
    {
        Log::spy();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Missing required fields
        $response = $this->postJson('/api/plants', []);
        $response->assertStatus(422);

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function ($message, $context) use ($user) {
                return $message === 'Plant creation validation failed' &&
                    $context['who']['user_id'] === $user->id &&
                    $context['what']['action'] === 'store_plant' &&
                    $context['why']['reason'] === 'Validation error' &&
                    in_array('name', $context['why']['failed_fields']);
            });
    }

    public function test_logging_warning_when_login_fails_without_leaking_credentials(): void
    {
        Log::spy();
        $user = User::factory()->create([
            'email' => 'gardener@example.com',
            'password' => bcrypt('correct_password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'gardener@example.com',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(401);

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                // Ensure no password or plain token is leaked into context
                $contextJson = json_encode($context);
                return str_contains($message, 'Authentication failed') &&
                    $context['who']['user_id'] === 'guest' &&
                    $context['what']['action'] === 'auth_login' &&
                    !str_contains($contextJson, 'wrong_password') &&
                    !str_contains($contextJson, 'correct_password');
            });
    }

    public function test_logging_info_when_login_succeeds(): void
    {
        Log::spy();
        $user = User::factory()->create([
            'email' => 'gardener@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'gardener@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200);

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(function ($message, $context) use ($user) {
                $contextJson = json_encode($context);
                return str_contains($message, 'User authenticated successfully') &&
                    $context['who']['user_id'] === $user->id &&
                    $context['what']['action'] === 'auth_login' &&
                    !str_contains($contextJson, 'secret123');
            });
    }
}
