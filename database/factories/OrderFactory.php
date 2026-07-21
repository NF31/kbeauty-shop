<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $subtotalCents = $this->faker->numberBetween(2000, 15000);
        $shippingCents = 590;

        return [
            'user_id' => User::factory(),
            'order_number' => 'KB-'.now()->year.'-'.$this->faker->unique()->numerify('#####'),
            'status' => OrderStatus::Pending,
            'shipping_address_id' => Address::factory(),
            'billing_address_id' => Address::factory(),
            'subtotal_cents' => $subtotalCents,
            'discount_cents' => 0,
            'shipping_cents' => $shippingCents,
            'tax_cents' => 0,
            'total_cents' => $subtotalCents + $shippingCents,
            'currency' => 'EUR',
            'coupon_id' => null,
            'notes' => null,
            'placed_at' => now(),
        ];
    }
}
