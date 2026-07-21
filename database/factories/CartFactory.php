<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Cart>
 */
class CartFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => null,
            'session_token' => Str::random(40),
            'currency' => 'EUR',
        ];
    }

    public function forUser(): static
    {
        return $this->state(fn () => [
            'user_id' => User::factory(),
            'session_token' => null,
        ]);
    }
}
