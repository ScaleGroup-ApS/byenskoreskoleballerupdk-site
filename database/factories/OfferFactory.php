<?php

namespace Database\Factories;

use App\Enums\OfferType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Offer>
 */
class OfferFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 1000, 30000),
            'type' => OfferType::Primary,
            'theory_lessons' => fake()->numberBetween(20, 30),
            'driving_lessons' => fake()->numberBetween(15, 30),
            'track_required' => true,
            'slippery_required' => true,
        ];
    }

    public function addon(): static
    {
        return $this->state(fn () => ['type' => OfferType::Addon]);
    }
}
