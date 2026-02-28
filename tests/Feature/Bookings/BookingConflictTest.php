<?php

use App\Actions\Bookings\CheckBookingConflicts;
use App\Models\Booking;
use App\Models\Student;
use App\Models\User;
use App\Models\Vehicle;

test('returns empty array when no conflicts exist', function () {
    $instructor = User::factory()->instructor()->create();
    $student = Student::factory()->create();
    $vehicle = Vehicle::factory()->create();

    $conflicts = (new CheckBookingConflicts)->handle(
        startsAt: '2026-03-01 10:00:00',
        endsAt: '2026-03-01 11:00:00',
        instructor: $instructor,
        student: $student,
        vehicle: $vehicle,
    );

    expect($conflicts)->toBeEmpty();
});

test('detects instructor conflict', function () {
    $instructor = User::factory()->instructor()->create();
    $student = Student::factory()->create();

    Booking::factory()->create([
        'instructor_id' => $instructor->id,
        'starts_at' => '2026-03-01 10:00:00',
        'ends_at' => '2026-03-01 11:00:00',
    ]);

    $conflicts = (new CheckBookingConflicts)->handle(
        startsAt: '2026-03-01 10:30:00',
        endsAt: '2026-03-01 11:30:00',
        instructor: $instructor,
        student: $student,
    );

    expect($conflicts)->toHaveCount(1);
    expect($conflicts[0])->toContain('Instruktøren');
});

test('detects student conflict', function () {
    $instructor = User::factory()->instructor()->create();
    $otherInstructor = User::factory()->instructor()->create();
    $student = Student::factory()->create();

    Booking::factory()->create([
        'instructor_id' => $otherInstructor->id,
        'student_id' => $student->id,
        'starts_at' => '2026-03-01 10:00:00',
        'ends_at' => '2026-03-01 11:00:00',
    ]);

    $conflicts = (new CheckBookingConflicts)->handle(
        startsAt: '2026-03-01 10:30:00',
        endsAt: '2026-03-01 11:30:00',
        instructor: $instructor,
        student: $student,
    );

    expect($conflicts)->toHaveCount(1);
    expect($conflicts[0])->toContain('Eleven');
});

test('detects vehicle conflict', function () {
    $instructor = User::factory()->instructor()->create();
    $student = Student::factory()->create();
    $vehicle = Vehicle::factory()->create();

    Booking::factory()->create([
        'vehicle_id' => $vehicle->id,
        'starts_at' => '2026-03-01 10:00:00',
        'ends_at' => '2026-03-01 11:00:00',
    ]);

    $conflicts = (new CheckBookingConflicts)->handle(
        startsAt: '2026-03-01 10:30:00',
        endsAt: '2026-03-01 11:30:00',
        instructor: $instructor,
        student: $student,
        vehicle: $vehicle,
    );

    expect($conflicts)->toHaveCount(1);
    expect($conflicts[0])->toContain('Køretøjet');
});

test('excludeBookingId ignores the booking being updated', function () {
    $instructor = User::factory()->instructor()->create();
    $student = Student::factory()->create();

    $existing = Booking::factory()->create([
        'instructor_id' => $instructor->id,
        'student_id' => $student->id,
        'starts_at' => '2026-03-01 10:00:00',
        'ends_at' => '2026-03-01 11:00:00',
    ]);

    $conflicts = (new CheckBookingConflicts)->handle(
        startsAt: '2026-03-01 10:00:00',
        endsAt: '2026-03-01 11:00:00',
        instructor: $instructor,
        student: $student,
        excludeBookingId: $existing->id,
    );

    expect($conflicts)->toBeEmpty();
});
