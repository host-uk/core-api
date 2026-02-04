<?php

declare(strict_types=1);

namespace Core\Api\Tests\Feature;

use Core\Api\Jobs\DeliverWebhookJob;
use Core\Api\Models\WebhookDelivery;
use Core\Api\Models\WebhookEndpoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebhookIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    protected WebhookEndpoint $endpoint;

    protected function setUp(): void
    {
        parent::setUp();

        \Illuminate\Support\Facades\Schema::create('workspaces', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        $migrationPaths = [
            'src/Api/Migrations/2026_01_07_002400_create_webhook_endpoints_table.php',
            'src/Api/Migrations/2026_01_07_002401_create_webhook_deliveries_table.php',
            'src/Api/Migrations/2026_01_26_200000_add_webhook_secret_rotation_fields.php',
            'src/Api/Migrations/2026_02_04_173731_add_processed_at_to_webhook_deliveries_table.php',
        ];

        foreach ($migrationPaths as $path) {
            $migration = include base_path($path);
            $migration->up();
        }

        \Illuminate\Support\Facades\DB::table('workspaces')->insert([
            'id' => 1,
            'name' => 'Test Workspace',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->endpoint = WebhookEndpoint::forceCreate([
            'workspace_id' => 1,
            'url' => 'https://example.com/webhook',
            'secret' => 'test_secret',
            'events' => ['bio.created'],
            'active' => true,
        ]);
    }

    public function test_it_skips_processing_if_attempt_mismatch()
    {
        Http::fake();

        $delivery = WebhookDelivery::forceCreate([
            'webhook_endpoint_id' => $this->endpoint->id,
            'event_id' => 'evt_123',
            'event_type' => 'bio.created',
            'payload' => ['data' => 'test'],
            'status' => WebhookDelivery::STATUS_QUEUED,
            'attempt' => 1,
        ]);

        // Create a job for attempt 1
        $job = new DeliverWebhookJob($delivery);

        // Manually increment attempt in DB to simulate another job already finished it or failed it
        $delivery->update(['attempt' => 2, 'status' => WebhookDelivery::STATUS_RETRYING]);

        $job->handle();

        // Http should NOT have been called because attemptMismatch
        Http::assertNothingSent();
    }

    public function test_it_skips_processing_if_already_successful()
    {
        Http::fake();

        $delivery = WebhookDelivery::forceCreate([
            'webhook_endpoint_id' => $this->endpoint->id,
            'event_id' => 'evt_123',
            'event_type' => 'bio.created',
            'payload' => ['data' => 'test'],
            'status' => WebhookDelivery::STATUS_SUCCESS,
            'attempt' => 1,
        ]);

        $job = new DeliverWebhookJob($delivery);
        $job->handle();

        Http::assertNothingSent();
    }

    public function test_it_marks_as_processing_and_sets_processed_at()
    {
        Http::fake([
            'example.com/*' => Http::response(['ok' => true], 200),
        ]);

        $delivery = WebhookDelivery::forceCreate([
            'webhook_endpoint_id' => $this->endpoint->id,
            'event_id' => 'evt_123',
            'event_type' => 'bio.created',
            'payload' => ['data' => 'test'],
            'status' => WebhookDelivery::STATUS_QUEUED,
            'attempt' => 1,
        ]);

        $job = new DeliverWebhookJob($delivery);
        $job->handle();

        $delivery->refresh();
        $this->assertEquals(WebhookDelivery::STATUS_SUCCESS, $delivery->status);
        $this->assertNotNull($delivery->processed_at);
    }
}
