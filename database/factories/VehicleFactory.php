<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vehicle>
 */
class VehicleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true).' #'.fake()->numberBetween(1, 10),
            'plate_number' => strtoupper(fake()->bothify('??#####')),
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['active' => false]);
    }
}
