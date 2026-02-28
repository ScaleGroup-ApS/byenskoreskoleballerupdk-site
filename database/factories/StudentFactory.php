<?php

namespace Database\Factories;

use App\Enums\StudentStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->student(),
            'phone' => fake()->phoneNumber(),
            'cpr' => fake()->numerify('######-####'),
            'status' => StudentStatus::Active,
            'start_date' => fake()->date(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => StudentStatus::Inactive]);
    }

    public function graduated(): static
    {
        return $this->state(fn () => ['status' => StudentStatus::Graduated]);
    }
}
