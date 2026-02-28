<?php

use App\Enums\BookingStatus;
use App\Enums\BookingType;
use App\Models\Booking;
use App\Models\Student;
use App\Models\User;
use App\Models\Vehicle;

test('booking belongs to student, instructor and vehicle', function () {
    $booking = Booking::factory()->create();

    expect($booking->student)->toBeInstanceOf(Student::class);
    expect($booking->instructor)->toBeInstanceOf(User::class);
    expect($booking->vehicle)->toBeInstanceOf(Vehicle::class);
});

test('booking casts type and status to enums', function () {
    $booking = Booking::factory()->create();

    expect($booking->type)->toBeInstanceOf(BookingType::class);
    expect($booking->status)->toBeInstanceOf(BookingStatus::class);
});

test('scopeOverlapping returns bookings that overlap the given range', function () {
    $instructor = User::factory()->instructor()->create();
    $student = Student::factory()->create();

    // Booking: 10:00–11:00
    Booking::factory()->create([
        'instructor_id' => $instructor->id,
        'student_id' => $student->id,
        'starts_at' => '2026-03-01 10:00:00',
        'ends_at' => '2026-03-01 11:00:00',
    ]);

    // Overlapping query: 10:30–11:30 → should find it
    $overlapping = Booking::query()
        ->where('instructor_id', $instructor->id)
        ->overlapping('2026-03-01 10:30:00', '2026-03-01 11:30:00')
        ->count();

    expect($overlapping)->toBe(1);
});

test('scopeOverlapping ignores cancelled bookings', function () {
    $instructor = User::factory()->instructor()->create();
    $student = Student::factory()->create();

    Booking::factory()->cancelled()->create([
        'instructor_id' => $instructor->id,
        'student_id' => $student->id,
        'starts_at' => '2026-03-01 10:00:00',
        'ends_at' => '2026-03-01 11:00:00',
    ]);

    $overlapping = Booking::query()
        ->where('instructor_id', $instructor->id)
        ->overlapping('2026-03-01 10:30:00', '2026-03-01 11:30:00')
        ->count();

    expect($overlapping)->toBe(0);
});

test('non-overlapping bookings are not returned by scope', function () {
    $instructor = User::factory()->instructor()->create();
    $student = Student::factory()->create();

    // Booking: 08:00–09:00
    Booking::factory()->create([
        'instructor_id' => $instructor->id,
        'student_id' => $student->id,
        'starts_at' => '2026-03-01 08:00:00',
        'ends_at' => '2026-03-01 09:00:00',
    ]);

    // Query: 10:00–11:00 → no overlap
    $overlapping = Booking::query()
        ->where('instructor_id', $instructor->id)
        ->overlapping('2026-03-01 10:00:00', '2026-03-01 11:00:00')
        ->count();

    expect($overlapping)->toBe(0);
});
