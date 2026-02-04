<?php

declare(strict_types=1);

namespace Core\Api\Controllers;

use Core\Api\Documentation\Attributes\ApiParameter;
use Core\Api\Documentation\Attributes\ApiResponse;
use Core\Api\Documentation\Attributes\ApiSecurity;
use Core\Api\Documentation\Attributes\ApiTag;
use Core\Api\Concerns\ResolvesWorkspace;
use Core\Api\Models\Product;
use Core\Api\Resources\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

/**
 * Product API Controller.
 *
 * Handles CRUD operations for commerce products.
 */
#[ApiTag('Products', 'Commerce product management endpoints')]
#[ApiSecurity('apiKey')]
class ProductApiController extends Controller
{
    use ResolvesWorkspace;

    /**
     * List all products.
     */
    #[ApiParameter('status', 'query', 'string', 'Filter by product status')]
    #[ApiParameter('per_page', 'query', 'integer', 'Items per page', false, 15)]
    #[ApiResponse(200, ProductResource::class, 'List of products', paginated: true)]
    #[ApiResponse(401, null, 'Unauthorized')]
    public function index(Request $request): AnonymousResourceCollection
    {
        $workspace = $this->resolveWorkspace($request);

        $products = Product::query()
            ->when($workspace, fn ($q) => $q->where('workspace_id', $workspace->id))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->paginate($request->query('per_page', 15));

        return ProductResource::collection($products);
    }

    /**
     * Get a specific product.
     */
    #[ApiResponse(200, ProductResource::class, 'Product details')]
    #[ApiResponse(401, null, 'Unauthorized')]
    #[ApiResponse(404, null, 'Product not found')]
    public function show(Request $request, Product $product): ProductResource
    {
        $this->ensureProductInWorkspace($request, $product);

        return new ProductResource($product);
    }

    /**
     * Create a new product.
     */
    #[ApiResponse(201, ProductResource::class, 'Product created')]
    #[ApiResponse(401, null, 'Unauthorized')]
    #[ApiResponse(422, null, 'Validation failed')]
    public function store(Request $request): JsonResponse
    {
        $workspace = $this->resolveWorkspace($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products,slug',
            'description' => 'nullable|string',
            'price' => 'required|integer|min:0',
            'currency' => 'required|string|size:3',
            'status' => 'nullable|string|max:20',
            'metadata' => 'nullable|array',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $validated['workspace_id'] = $workspace?->id;

        $product = Product::create($validated);

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update an existing product.
     */
    #[ApiResponse(200, ProductResource::class, 'Product updated')]
    #[ApiResponse(401, null, 'Unauthorized')]
    #[ApiResponse(404, null, 'Product not found')]
    #[ApiResponse(422, null, 'Validation failed')]
    public function update(Request $request, Product $product): ProductResource
    {
        $this->ensureProductInWorkspace($request, $product);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products,slug,' . $product->id,
            'description' => 'nullable|string',
            'price' => 'nullable|integer|min:0',
            'currency' => 'nullable|string|size:3',
            'status' => 'nullable|string|max:20',
            'metadata' => 'nullable|array',
        ]);

        $product->update($validated);

        return new ProductResource($product);
    }

    /**
     * Delete a product.
     */
    #[ApiResponse(204, null, 'Product deleted')]
    #[ApiResponse(401, null, 'Unauthorized')]
    #[ApiResponse(404, null, 'Product not found')]
    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->ensureProductInWorkspace($request, $product);

        $product->delete();

        return response()->json(null, 204);
    }

    /**
     * Ensure product belongs to the resolved workspace.
     */
    protected function ensureProductInWorkspace(Request $request, Product $product): void
    {
        $workspace = $this->resolveWorkspace($request);

        if ($workspace && $product->workspace_id !== $workspace->id) {
            abort(404);
        }
    }
}
