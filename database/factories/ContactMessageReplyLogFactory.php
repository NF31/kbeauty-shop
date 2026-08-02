<?php

namespace Database\Factories;

use App\Models\ContactMessage;
use App\Models\ContactMessageReplyLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactMessageReplyLog>
 */
class ContactMessageReplyLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'contact_message_id' => ContactMessage::factory(),
            'user_id' => User::factory(),
            'message' => fake()->paragraph(),
        ];
    }
}
