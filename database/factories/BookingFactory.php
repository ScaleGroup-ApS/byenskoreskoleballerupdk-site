<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Enums\BookingType;
use App\Models\Student;
use App\Models\Team;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('+1 day', '+30 days');
        $endsAt = (clone $startsAt)->modify('+45 minutes');

        return [
            'student_id' => Student::factory(),
            'team_id' => null,
            'instructor_id' => User::factory()->instructor(),
            'vehicle_id' => Vehicle::factory(),
            'type' => BookingType::DrivingLesson,
            'status' => BookingStatus::Scheduled,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'notes' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => BookingStatus::Completed]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => BookingStatus::Cancelled]);
    }

    public function theory(): static
    {
        return $this->state(fn () => ['type' => BookingType::TheoryLesson, 'vehicle_id' => null]);
    }

    public function forTeam(Team $team): static
    {
        return $this->state(fn () => ['team_id' => $team->id]);
    }
}
