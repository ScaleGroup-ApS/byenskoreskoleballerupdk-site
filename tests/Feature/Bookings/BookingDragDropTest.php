<?php

use App\Models\Booking;
use App\Models\Student;
use App\Models\User;

use function Pest\Laravel\actingAs;

test('admin can reschedule a booking via drag and drop', function () {
    $admin = User::factory()->create();
    $booking = Booking::factory()->create([
        'starts_at' => '2026-03-10 10:00:00',
        'ends_at' => '2026-03-10 10:45:00',
    ]);

    actingAs($admin)
        ->from(route('bookings.index'))
        ->patch(route('bookings.update', $booking), [
            'starts_at' => '2026-03-10 14:00:00',
            'ends_at' => '2026-03-10 14:45:00',
        ])
        ->assertRedirect(route('bookings.index'));

    expect($booking->fresh()->starts_at->format('H:i'))->toBe('14:00');
    expect($booking->fresh()->ends_at->format('H:i'))->toBe('14:45');
});

test('drag and drop is rejected when new slot conflicts', function () {
    $admin = User::factory()->create();
    $instructor = User::factory()->instructor()->create();
    $student = Student::factory()->create();

    // Existing booking at 14:00
    Booking::factory()->create([
        'instructor_id' => $instructor->id,
        'student_id' => $student->id,
        'starts_at' => '2026-03-10 14:00:00',
        'ends_at' => '2026-03-10 14:45:00',
    ]);

    // Booking being dragged
    $dragged = Booking::factory()->create([
        'instructor_id' => $instructor->id,
        'starts_at' => '2026-03-10 10:00:00',
        'ends_at' => '2026-03-10 10:45:00',
    ]);

    actingAs($admin)
        ->patch(route('bookings.update', $dragged), [
            'starts_at' => '2026-03-10 14:15:00',
            'ends_at' => '2026-03-10 15:00:00',
        ])
        ->assertSessionHasErrors('conflicts');

    // Original times preserved
    expect($dragged->fresh()->starts_at->format('H:i'))->toBe('10:00');
});

test('instructor can drag and drop own bookings', function () {
    $instructor = User::factory()->instructor()->create();

    $booking = Booking::factory()->create([
        'instructor_id' => $instructor->id,
        'starts_at' => '2026-03-10 10:00:00',
        'ends_at' => '2026-03-10 10:45:00',
    ]);

    actingAs($instructor)
        ->from(route('bookings.index'))
        ->patch(route('bookings.update', $booking), [
            'starts_at' => '2026-03-10 11:00:00',
            'ends_at' => '2026-03-10 11:45:00',
        ])
        ->assertRedirect(route('bookings.index'));

    expect($booking->fresh()->starts_at->format('H:i'))->toBe('11:00');
});
