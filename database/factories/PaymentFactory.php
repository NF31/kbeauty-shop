<?php

namespace Database\Factories;

use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'provider' => PaymentProvider::Stripe,
            'provider_payment_id' => 'pi_'.$this->faker->unique()->regexify('[a-zA-Z0-9]{24}'),
            'status' => PaymentStatus::Pending,
            'amount_cents' => $this->faker->numberBetween(2000, 15000),
            'paid_at' => null,
        ];
    }
}
