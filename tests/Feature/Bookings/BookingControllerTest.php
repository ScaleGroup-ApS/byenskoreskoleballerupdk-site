<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Student;
use App\Models\User;
use App\Models\Vehicle;

use function Pest\Laravel\actingAs;

test('admin can view bookings index', function () {
    $admin = User::factory()->create();

    actingAs($admin)->get(route('bookings.index'))->assertOk();
});

test('instructor can view bookings index', function () {
    $instructor = User::factory()->instructor()->create();

    actingAs($instructor)->get(route('bookings.index'))->assertOk();
});

test('student cannot view bookings index', function () {
    $student = Student::factory()->create();

    actingAs($student->user)->get(route('bookings.index'))->assertForbidden();
});

test('admin can create a booking', function () {
    $admin = User::factory()->create();
    $student = Student::factory()->create();
    $instructor = User::factory()->instructor()->create();
    $vehicle = Vehicle::factory()->create();

    actingAs($admin)
        ->post(route('bookings.store'), [
            'student_id' => $student->id,
            'instructor_id' => $instructor->id,
            'vehicle_id' => $vehicle->id,
            'type' => 'driving_lesson',
            'starts_at' => '2026-03-10 10:00:00',
            'ends_at' => '2026-03-10 10:45:00',
        ])
        ->assertRedirect(route('bookings.index'));

    expect(Booking::count())->toBe(1);
});

test('booking store rejects conflict', function () {
    $admin = User::factory()->create();
    $student = Student::factory()->create();
    $instructor = User::factory()->instructor()->create();

    Booking::factory()->create([
        'student_id' => $student->id,
        'instructor_id' => $instructor->id,
        'starts_at' => '2026-03-10 10:00:00',
        'ends_at' => '2026-03-10 10:45:00',
    ]);

    actingAs($admin)
        ->post(route('bookings.store'), [
            'student_id' => $student->id,
            'instructor_id' => $instructor->id,
            'type' => 'driving_lesson',
            'starts_at' => '2026-03-10 10:15:00',
            'ends_at' => '2026-03-10 11:00:00',
        ])
        ->assertSessionHasErrors('conflicts');
});

test('admin can cancel a booking', function () {
    $admin = User::factory()->create();
    $booking = Booking::factory()->create();

    actingAs($admin)
        ->delete(route('bookings.destroy', $booking))
        ->assertRedirect(route('bookings.index'));

    expect($booking->fresh()->status)->toBe(BookingStatus::Cancelled);
});

test('admin can mark a booking as completed', function () {
    $admin = User::factory()->create();
    $booking = Booking::factory()->create();

    actingAs($admin)
        ->from(route('bookings.index'))
        ->patch(route('bookings.update', $booking), [
            'status' => 'completed',
        ])
        ->assertRedirect(route('bookings.index'));

    expect($booking->fresh()->status)->toBe(BookingStatus::Completed);
});

test('instructor can only update own bookings', function () {
    $instructor = User::factory()->instructor()->create();
    $otherInstructor = User::factory()->instructor()->create();

    $booking = Booking::factory()->create([
        'instructor_id' => $otherInstructor->id,
    ]);

    actingAs($instructor)
        ->patch(route('bookings.update', $booking), [
            'notes' => 'test',
        ])
        ->assertForbidden();
});
