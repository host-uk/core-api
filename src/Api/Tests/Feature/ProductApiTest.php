<?php

declare(strict_types=1);

use Core\Api\Models\ApiKey;
use Core\Api\Models\Product;
use Core\Tenant\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->workspace = Workspace::factory()->create();
    $this->apiKey = ApiKey::factory()->create([
        'workspace_id' => $this->workspace->id,
        'scopes' => ['*'],
    ]);

    // Auth as the API key
    $this->withHeader('X-API-Key', $this->apiKey->prefix . '_' . 'test_key');
    // Note: AuthenticateApiKey middleware expects prefix_plainKey
    // In tests we might need to mock the verification or use a real key
});

it('can list products in workspace', function () {
    Product::factory()->count(3)->create(['workspace_id' => $this->workspace->id]);
    Product::factory()->count(2)->create(['workspace_id' => Workspace::factory()->create()->id]);

    $response = $this->getJson('/api/products');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('can show a product', function () {
    $product = Product::factory()->create(['workspace_id' => $this->workspace->id]);

    $response = $this->getJson("/api/products/{$product->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $product->id)
        ->assertJsonPath('data.name', $product->name);
});

it('cannot show a product from another workspace', function () {
    $otherWorkspace = Workspace::factory()->create();
    $product = Product::factory()->create(['workspace_id' => $otherWorkspace->id]);

    $response = $this->getJson("/api/products/{$product->id}");

    $response->assertStatus(404);
});

it('can create a product', function () {
    $data = [
        'name' => 'Test Product',
        'price' => 1000,
        'currency' => 'GBP',
        'description' => 'A test product',
        'status' => 'active',
    ];

    $response = $this->postJson('/api/products', $data);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'Test Product')
        ->assertJsonPath('data.price.amount', 1000);

    $this->assertDatabaseHas('products', [
        'name' => 'Test Product',
        'workspace_id' => $this->workspace->id,
    ]);
});

it('can update a product', function () {
    $product = Product::factory()->create(['workspace_id' => $this->workspace->id]);

    $response = $this->putJson("/api/products/{$product->id}", [
        'name' => 'Updated Name',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'Updated Name');

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'Updated Name',
    ]);
});

it('can delete a product', function () {
    $product = Product::factory()->create(['workspace_id' => $this->workspace->id]);

    $response = $this->deleteJson("/api/products/{$product->id}");

    $response->assertStatus(204);

    $this->assertSoftDeleted('products', ['id' => $product->id]);
});
