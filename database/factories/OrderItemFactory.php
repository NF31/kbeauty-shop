<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        $unitPriceCents = $this->faker->numberBetween(500, 5000);
        $quantity = $this->faker->numberBetween(1, 3);

        return [
            'order_id' => Order::factory(),
            'product_variant_id' => ProductVariant::factory(),
            'product_name' => $this->faker->words(3, true),
            'variant_label' => $this->faker->randomElement(['50ml', '100ml', 'Standard']),
            'product_image_path' => null,
            'unit_price_cents' => $unitPriceCents,
            'quantity' => $quantity,
            'total_cents' => $unitPriceCents * $quantity,
            'is_gift' => false,
        ];
    }
}
