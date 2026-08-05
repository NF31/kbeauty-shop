<?php

namespace Database\Factories;

use App\Enums\ReturnRequestStatus;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReturnRequest>
 */
class ReturnRequestFactory extends Factory
{
    /**
     * Pas de résolution automatique de `user_id` depuis `order_id` (les deux
     * factories imbriquées se résolvent indépendamment) — passer `for()`/un
     * `user_id` explicite cohérent avec la commande dans les tests qui en
     * ont besoin.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'user_id' => User::factory(),
            'status' => ReturnRequestStatus::Submitted,
            'reason' => fake()->sentence(),
        ];
    }
}
