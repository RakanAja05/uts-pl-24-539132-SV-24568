<?php
namespace Tests\Feature;

use App\Models\Plant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlantApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_tanpa_token_ditolak_401(): void
    {
        $this->getJson('/api/plants')->assertStatus(401);
    }

    public function test_bisa_membuat_plant_dengan_status_201(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/plants', [
            'name' => 'Monstera',
            'species' => 'Monstera deliciosa',
            'watering_interval_days' => 7,
        ])->assertStatus(201)->assertJsonFragment(['name' => 'Monstera']);

        $this->assertDatabaseHas('plants', ['name' => 'Monstera']);
    }

    public function test_validasi_gagal_422(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/plants', [])->assertStatus(422);
    }

    public function test_bisa_melihat_daftar_plant(): void
    {
        Sanctum::actingAs(User::factory()->create());
        Plant::factory()->count(3)->create();

        $this->getJson('/api/plants')->assertOk()->assertJsonCount(3);
    }
}
