<?php

declare(strict_types=1);

namespace Core\Api\Models;

use Core\Api\Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Product model.
 *
 * Represents a product in the commerce system.
 */
class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'workspace_id',
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'status',
        'metadata',
    ];

    protected $casts = [
        'price' => 'integer', // Stored in cents/pence
        'metadata' => 'array',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }

    /**
     * Get the formatted price with currency.
     */
    public function getFormattedPriceAttribute(): string
    {
        $symbol = $this->currency === 'GBP' ? '£' : '$';
        return $symbol . number_format($this->price / 100, 2);
    }
}
