<?php

namespace Database\Factories;

use App\Enums\ConversationType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Conversation>
 */
class ConversationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => ConversationType::Direct,
            'name' => null,
            'team_id' => null,
        ];
    }

    public function group(): static
    {
        return $this->state(fn () => [
            'type' => ConversationType::Group,
            'name' => fake()->words(3, true),
        ]);
    }
}
