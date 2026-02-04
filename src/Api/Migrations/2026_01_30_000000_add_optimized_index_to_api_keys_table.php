<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds a partial index on API key prefixes to optimize the findByPlainKey lookup.
     * This ensures that the database only searches among active (non-deleted) keys.
     * Partial indexes are much smaller and faster than full indexes.
     */
    public function up(): void
    {
        // Partial indexes improve lookup performance.
        // They are supported in SQLite, PostgreSQL, and MySQL 8.0.13+.
        // We only filter by deleted_at IS NULL as it's immutable, unlike timestamp comparisons.
        try {
            $driver = DB::connection()->getDriverName();

            if ($driver === 'sqlite' || $driver === 'pgsql') {
                DB::statement("CREATE INDEX api_keys_prefix_active_idx ON api_keys (prefix) WHERE deleted_at IS NULL");
            } else {
                // For MySQL 8.0.13+, partial indexes are supported but the syntax differs.
                // For simplicity and compatibility, we fall back to a standard index
                // if we can't safely use the partial index syntax.
                Schema::table('api_keys', function (Blueprint $table) {
                    $table->index('prefix', 'api_keys_prefix_active_idx');
                });
            }
        } catch (\Exception $e) {
            // Safety fallback: if anything fails, try to create a standard index
            if (!Schema::hasIndex('api_keys', 'api_keys_prefix_active_idx')) {
                Schema::table('api_keys', function (Blueprint $table) {
                    $table->index('prefix', 'api_keys_prefix_active_idx');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropIndex('api_keys_prefix_active_idx');
        });
    }
};
