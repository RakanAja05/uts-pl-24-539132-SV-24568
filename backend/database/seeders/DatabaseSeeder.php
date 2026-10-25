<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // firstOrCreate aman dijalankan berulang kali (idempotent).
        // Dosen cukup jalankan `docker compose up -d --build` dan
        // akun login ini langsung tersedia tanpa error duplicate entry.
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'     => 'Admin',
                'password' => bcrypt('password'),
            ]
        );
    }
}
