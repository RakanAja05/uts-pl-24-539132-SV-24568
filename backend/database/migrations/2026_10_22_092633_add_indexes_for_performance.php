<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add database indexes to improve API response performance.
 *
 * personal_access_tokens:
 *   - tokenable_type + tokenable_id composite index for eager-loading the user
 *     after token lookup (Sanctum does `$accessToken->tokenable`).
 *
 * plants:
 *   - created_at index to speed up Plant::latest()->get() ordering.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Index for Sanctum eager-loading tokenable (user) after token lookup.
        if (! $this->indexExists('personal_access_tokens', 'pat_tokenable_idx')) {
            Schema::table('personal_access_tokens', function (Blueprint $table) {
                $table->index(['tokenable_type', 'tokenable_id'], 'pat_tokenable_idx');
            });
        }

        // Index for ordering plants by newest first.
        if (! $this->indexExists('plants', 'plants_created_at_idx')) {
            Schema::table('plants', function (Blueprint $table) {
                $table->index('created_at', 'plants_created_at_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropIndex('pat_tokenable_idx');
        });

        Schema::table('plants', function (Blueprint $table) {
            $table->dropIndex('plants_created_at_idx');
        });
    }

    /**
     * Check if an index already exists — works on both MySQL and SQLite.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite: query sqlite_master for indexes
            return DB::table('sqlite_master')
                ->where('type', 'index')
                ->where('tbl_name', $table)
                ->where('name', $indexName)
                ->exists();
        }

        // MySQL / MariaDB
        $database = DB::connection()->getDatabaseName();
        return DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }
};
