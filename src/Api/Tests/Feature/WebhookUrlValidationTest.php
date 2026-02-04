<?php

declare(strict_types=1);

namespace Core\Api\Tests\Feature;

use Core\Api\Jobs\DeliverWebhookJob;
use Core\Api\Models\WebhookDelivery;
use Core\Api\Models\WebhookEndpoint;
use Core\Api\Services\WebhookUrlValidator;
use Core\Tenant\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebhookUrlValidationTest extends TestCase
{
    use RefreshDatabase;

    protected Workspace $workspace;
    protected WebhookUrlValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workspace = Workspace::factory()->create();
        $this->validator = app(WebhookUrlValidator::class);
        Http::fake();
    }

    /** @test */
    public function it_blocks_localhost()
    {
        $this->assertFalse($this->validator->validate('http://localhost/webhook'));
        $this->assertFalse($this->validator->validate('https://localhost/webhook'));
        $this->assertFalse($this->validator->validate('http://localhost.localdomain/webhook'));
    }

    /** @test */
    public function it_blocks_private_ipv4_addresses()
    {
        $this->assertFalse($this->validator->validate('http://127.0.0.1/webhook'));
        $this->assertFalse($this->validator->validate('http://10.0.0.1/webhook'));
        $this->assertFalse($this->validator->validate('http://172.16.0.1/webhook'));
        $this->assertFalse($this->validator->validate('http://192.168.1.1/webhook'));
    }

    /** @test */
    public function it_blocks_link_local_and_metadata_addresses()
    {
        $this->assertFalse($this->validator->validate('http://169.254.169.254/latest/meta-data'));
        $this->assertFalse($this->validator->validate('http://169.254.0.1/webhook'));
    }

    /** @test */
    public function it_blocks_loopback_ipv6_addresses()
    {
        $this->assertFalse($this->validator->validate('http://[::1]/webhook'));
        $this->assertFalse($this->validator->validate('http://[0000:0000:0000:0000:0000:0000:0000:0001]/webhook'));
    }

    /** @test */
    public function it_blocks_non_http_schemes()
    {
        $this->assertFalse($this->validator->validate('ftp://example.com/webhook'));
        $this->assertFalse($this->validator->validate('file:///etc/passwd'));
        $this->assertFalse($this->validator->validate('gopher://example.com'));
    }

    /** @test */
    public function it_allows_valid_public_urls()
    {
        // Using IP addresses that are definitely public
        $this->assertTrue($this->validator->validate('https://8.8.8.8/webhook'));
        $this->assertTrue($this->validator->validate('https://1.1.1.1/webhook'));

        // Hostnames should also work if they resolve to public IPs
        $this->assertTrue($this->validator->validate('https://example.com/webhook'));
    }

    /** @test */
    public function it_throws_exception_when_creating_endpoint_with_restricted_url()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid or restricted webhook URL.');

        WebhookEndpoint::createForWorkspace(
            $this->workspace->id,
            'http://127.0.0.1/webhook',
            ['*']
        );
    }

    /** @test */
    public function it_throws_exception_when_updating_endpoint_to_restricted_url()
    {
        $endpoint = WebhookEndpoint::createForWorkspace(
            $this->workspace->id,
            'https://example.com/webhook',
            ['*']
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid or restricted webhook URL.');

        $endpoint->url = 'http://10.0.0.1/webhook';
        $endpoint->save();
    }

    /** @test */
    public function it_cancels_delivery_if_url_is_restricted_at_runtime()
    {
        // Create with valid URL first
        $endpoint = WebhookEndpoint::createForWorkspace(
            $this->workspace->id,
            'https://example.com/webhook',
            ['*']
        );

        // Bypass the mutator to set an invalid URL
        // This simulates a URL that was valid but now points to a restricted IP,
        // or a URL that was changed directly in the database.
        $endpoint->getAttributes(); // ensure attributes are loaded
        $endpoint->setRawAttributes(array_merge($endpoint->getAttributes(), [
            'url' => 'http://127.0.0.1/webhook',
        ]));
        $endpoint->save();

        $delivery = WebhookDelivery::createForEvent($endpoint, 'test.event', ['foo' => 'bar']);

        $job = new DeliverWebhookJob($delivery);
        $job->handle();

        $delivery->refresh();
        $this->assertEquals(WebhookDelivery::STATUS_FAILED, $delivery->status);
        $this->assertEquals('Restricted URL blocked for security reasons.', $delivery->response_body);
        $this->assertEquals(WebhookDelivery::MAX_RETRIES, $delivery->attempt);

        Http::assertNothingSent();
    }
}
