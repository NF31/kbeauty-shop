<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'number' => 'KB-'.now()->year.'-'.$this->faker->unique()->numerify('#####'),
            'path' => 'invoices/fake.pdf',
            'total_cents' => $this->faker->numberBetween(2000, 15000),
            'currency' => 'EUR',
            'issued_at' => now(),
        ];
    }
}
