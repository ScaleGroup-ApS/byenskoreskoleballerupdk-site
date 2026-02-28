<?php

use App\Actions\Bookings\CancelBooking;
use App\Actions\Bookings\CompleteBooking;
use App\Models\Booking;
use App\Models\Student;
use App\States\StudentProgressionState;
use Thunk\Verbs\Facades\Verbs;

test('completing a booking increments lesson count by type', function () {
    $student = Student::factory()->create();
    $booking = Booking::factory()->create([
        'student_id' => $student->id,
        'type' => 'driving_lesson',
    ]);

    (new CompleteBooking)->handle($booking);

    Verbs::commit();

    $state = StudentProgressionState::load($student->id);

    expect($state->lesson_counts['driving_lesson'])->toBe(1);
});

test('completing multiple bookings accumulates counts per type', function () {
    $student = Student::factory()->create();

    $driving1 = Booking::factory()->create(['student_id' => $student->id, 'type' => 'driving_lesson']);
    $driving2 = Booking::factory()->create(['student_id' => $student->id, 'type' => 'driving_lesson']);
    $theory = Booking::factory()->create(['student_id' => $student->id, 'type' => 'theory_lesson']);

    (new CompleteBooking)->handle($driving1);
    (new CompleteBooking)->handle($driving2);
    (new CompleteBooking)->handle($theory);

    Verbs::commit();

    $state = StudentProgressionState::load($student->id);

    expect($state->lesson_counts['driving_lesson'])->toBe(2);
    expect($state->lesson_counts['theory_lesson'])->toBe(1);
});

test('cancelling a completed booking decrements lesson count', function () {
    $student = Student::factory()->create();
    $booking = Booking::factory()->create([
        'student_id' => $student->id,
        'type' => 'driving_lesson',
    ]);

    (new CompleteBooking)->handle($booking);
    (new CancelBooking)->handle($booking->fresh());

    Verbs::commit();

    $state = StudentProgressionState::load($student->id);

    expect($state->lesson_counts['driving_lesson'])->toBe(0);
});

test('cancelling a scheduled booking does not affect lesson count', function () {
    $student = Student::factory()->create();
    $booking = Booking::factory()->create([
        'student_id' => $student->id,
        'type' => 'driving_lesson',
    ]);

    (new CancelBooking)->handle($booking);

    Verbs::commit();

    $state = StudentProgressionState::load($student->id);

    expect($state->lesson_counts['driving_lesson'] ?? 0)->toBe(0);
});
