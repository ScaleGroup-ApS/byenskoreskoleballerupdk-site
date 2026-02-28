<?php

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Jobs\FlagNoShows;
use App\Models\Booking;
use App\Models\Student;
use App\Models\User;
use Thunk\Verbs\Facades\Verbs;

test('past scheduled bookings are flagged as no-show', function () {
    $instructor = User::factory()->create(['role' => UserRole::Instructor]);
    $student = Student::factory()->create();

    $past = Booking::factory()->create([
        'student_id' => $student->id,
        'instructor_id' => $instructor->id,
        'status' => BookingStatus::Scheduled,
        'starts_at' => now()->subHours(3),
        'ends_at' => now()->subHours(2),
    ]);

    (new FlagNoShows)->handle();

    Verbs::commit();

    expect($past->fresh()->status)->toBe(BookingStatus::NoShow);
});

test('future scheduled bookings are not flagged', function () {
    $instructor = User::factory()->create(['role' => UserRole::Instructor]);
    $student = Student::factory()->create();

    $future = Booking::factory()->create([
        'student_id' => $student->id,
        'instructor_id' => $instructor->id,
        'status' => BookingStatus::Scheduled,
        'starts_at' => now()->addHours(2),
        'ends_at' => now()->addHours(3),
    ]);

    (new FlagNoShows)->handle();

    Verbs::commit();

    expect($future->fresh()->status)->toBe(BookingStatus::Scheduled);
});

test('already completed bookings are not affected', function () {
    $instructor = User::factory()->create(['role' => UserRole::Instructor]);
    $student = Student::factory()->create();

    $completed = Booking::factory()->create([
        'student_id' => $student->id,
        'instructor_id' => $instructor->id,
        'status' => BookingStatus::Completed,
        'starts_at' => now()->subHours(3),
        'ends_at' => now()->subHours(2),
    ]);

    (new FlagNoShows)->handle();

    Verbs::commit();

    expect($completed->fresh()->status)->toBe(BookingStatus::Completed);
});
