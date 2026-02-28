<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'amount' => fake()->randomFloat(2, 500, 20000),
            'method' => PaymentMethod::Card,
            'recorded_at' => now(),
            'notes' => null,
        ];
    }
}
