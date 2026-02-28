<?php

use App\Actions\Bookings\CancelBooking;
use App\Actions\Payments\RecordPayment;
use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Student;
use App\Models\User;
use App\Notifications\BookingCancelledNotification;
use App\Notifications\PaymentReceivedNotification;
use Illuminate\Support\Facades\Notification;
use Thunk\Verbs\Facades\Verbs;

test('student is notified when booking is cancelled', function () {
    Notification::fake();

    $instructor = User::factory()->create(['role' => UserRole::Instructor]);
    $student = Student::factory()->for(
        User::factory()->create(['role' => UserRole::Student]),
        'user',
    )->create();

    $booking = Booking::factory()->create([
        'student_id' => $student->id,
        'instructor_id' => $instructor->id,
        'status' => BookingStatus::Scheduled,
    ]);

    (new CancelBooking)->handle($booking);

    Verbs::commit();

    Notification::assertSentTo($student->user, BookingCancelledNotification::class);
});

test('student is notified when payment is recorded', function () {
    Notification::fake();

    $student = Student::factory()->for(
        User::factory()->create(['role' => UserRole::Student]),
        'user',
    )->create();

    (new RecordPayment)->handle([
        'student_id' => $student->id,
        'amount' => 1500,
        'method' => 'card',
        'notes' => null,
    ]);

    Verbs::commit();

    Notification::assertSentTo($student->user, PaymentReceivedNotification::class);
});

test('booking cancelled notification uses mail and database channels', function () {
    $instructor = User::factory()->create(['role' => UserRole::Instructor]);
    $student = Student::factory()->for(
        User::factory()->create(['role' => UserRole::Student]),
        'user',
    )->create();

    $booking = Booking::factory()->create([
        'student_id' => $student->id,
        'instructor_id' => $instructor->id,
    ]);

    $notification = new BookingCancelledNotification($booking);

    expect($notification->via($student->user))->toBe(['mail', 'database']);
});
