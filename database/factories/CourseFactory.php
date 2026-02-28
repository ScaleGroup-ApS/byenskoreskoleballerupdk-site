<?php

namespace Database\Factories;

use App\Models\Offer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Course>
 */
class CourseFactory extends Factory
{
    public function definition(): array
    {
        $startAt = fake()->dateTimeBetween('now', '+6 months');

        return [
            'offer_id' => Offer::factory(),
            'start_at' => $startAt->format('Y-m-d H:i:s'),
            'end_at' => (clone $startAt)->modify('+8 hours')->format('Y-m-d H:i:s'),
            'max_students' => null,
        ];
    }

    public function past(): static
    {
        return $this->state(function () {
            $startAt = fake()->dateTimeBetween('-6 months', '-1 day');

            return [
                'start_at' => $startAt->format('Y-m-d H:i:s'),
                'end_at' => (clone $startAt)->modify('+8 hours')->format('Y-m-d H:i:s'),
            ];
        });
    }
}
