<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('webhook_deliveries', function (Blueprint $table) {
            // Remove the redundant full index confirmed in 2026_01_07_002401_create_webhook_deliveries_table.php
            $table->dropIndex(['status', 'next_retry_at']);
        });

        $driver = DB::connection()->getDriverName();

        // Partial indexes are supported by PostgreSQL and SQLite (3.8.0+)
        if (in_array($driver, ['pgsql', 'sqlite'])) {
            DB::statement("CREATE INDEX webhook_deliveries_needs_delivery_idx ON webhook_deliveries (status, next_retry_at) WHERE status IN ('pending', 'retrying')");
        } else {
            Schema::table('webhook_deliveries', function (Blueprint $table) {
                // Fallback for MySQL and others that do not support partial indexes in this way
                $table->index(['status', 'next_retry_at'], 'webhook_deliveries_needs_delivery_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('webhook_deliveries', function (Blueprint $table) {
            $table->dropIndex('webhook_deliveries_needs_delivery_idx');

            // Restore the original full index
            $table->index(['status', 'next_retry_at']);
        });
    }
};
