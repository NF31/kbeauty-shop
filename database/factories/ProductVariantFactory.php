<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-########')),
            'price_cents' => fake()->numberBetween(500, 8000),
            'compare_at_price_cents' => null,
            'currency' => 'EUR',
            'weight_grams' => fake()->numberBetween(20, 500),
            'stock_quantity' => fake()->numberBetween(0, 200),
            'is_default' => false,
            'position' => 0,
        ];
    }

    public function default(): static
    {
        return $this->state(fn () => [
            'is_default' => true,
        ]);
    }
}
