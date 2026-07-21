<?php

namespace Database\Factories;

use App\Enums\AddressType;
use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => AddressType::Shipping,
            'full_name' => $this->faker->name(),
            'line1' => $this->faker->streetAddress(),
            'line2' => null,
            'postal_code' => $this->faker->postcode(),
            'city' => $this->faker->city(),
            'country_code' => 'FR',
            'phone' => null,
            'is_default' => false,
        ];
    }

    public function guest(): static
    {
        return $this->state(fn () => ['user_id' => null]);
    }

    public function billing(): static
    {
        return $this->state(fn () => ['type' => AddressType::Billing]);
    }
}
