<?php

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Jobs\SendBookingReminder;
use App\Models\Booking;
use App\Models\Student;
use App\Models\User;
use App\Notifications\BookingReminderNotification;
use Illuminate\Support\Facades\Notification;

test('reminder is sent to student and instructor for bookings 23-25h away', function () {
    Notification::fake();

    $instructor = User::factory()->create(['role' => UserRole::Instructor]);
    $student = Student::factory()->for(User::factory()->create(['role' => UserRole::Student]), 'user')->create();

    Booking::factory()->create([
        'student_id' => $student->id,
        'instructor_id' => $instructor->id,
        'status' => BookingStatus::Scheduled,
        'starts_at' => now()->addHours(24),
        'ends_at' => now()->addHours(25),
    ]);

    (new SendBookingReminder)->handle();

    Notification::assertSentTo($student->user, BookingReminderNotification::class);
    Notification::assertSentTo($instructor, BookingReminderNotification::class);
});

test('reminder is not sent for bookings outside the 23-25h window', function () {
    Notification::fake();

    $instructor = User::factory()->create(['role' => UserRole::Instructor]);
    $student = Student::factory()->for(User::factory()->create(['role' => UserRole::Student]), 'user')->create();

    // Too soon (1 hour away)
    Booking::factory()->create([
        'student_id' => $student->id,
        'instructor_id' => $instructor->id,
        'status' => BookingStatus::Scheduled,
        'starts_at' => now()->addHour(),
        'ends_at' => now()->addHours(2),
    ]);

    // Too far (48 hours away)
    Booking::factory()->create([
        'student_id' => $student->id,
        'instructor_id' => $instructor->id,
        'status' => BookingStatus::Scheduled,
        'starts_at' => now()->addHours(48),
        'ends_at' => now()->addHours(49),
    ]);

    (new SendBookingReminder)->handle();

    Notification::assertNothingSent();
});

test('reminder is not sent for cancelled bookings', function () {
    Notification::fake();

    $instructor = User::factory()->create(['role' => UserRole::Instructor]);
    $student = Student::factory()->for(User::factory()->create(['role' => UserRole::Student]), 'user')->create();

    Booking::factory()->create([
        'student_id' => $student->id,
        'instructor_id' => $instructor->id,
        'status' => BookingStatus::Cancelled,
        'starts_at' => now()->addHours(24),
        'ends_at' => now()->addHours(25),
    ]);

    (new SendBookingReminder)->handle();

    Notification::assertNothingSent();
});
