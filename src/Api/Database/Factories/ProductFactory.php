<?php

declare(strict_types=1);

namespace Core\Api\Database\Factories;

use Core\Api\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = $this->faker->words(3, true);

        return [
            'workspace_id' => 1,
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->paragraph(),
            'price' => $this->faker->numberBetween(100, 10000),
            'currency' => 'GBP',
            'status' => 'active',
            'metadata' => [],
        ];
    }
}
