<?php

use App\Actions\Bookings\CreateBooking;
use App\Actions\Bookings\UpdateBooking;
use App\Events\BookingUpdated;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Thunk\Verbs\Facades\Verbs;

test('updating a booking fires BookingUpdated event', function () {
    $student = Student::factory()->create();

    $booking = (new CreateBooking)->handle([
        'student_id' => $student->id,
        'type' => 'driving_lesson',
        'starts_at' => '2026-04-01 10:00:00',
        'ends_at' => '2026-04-01 10:45:00',
    ]);

    Verbs::commit();

    (new UpdateBooking)->handle($booking, [
        'starts_at' => '2026-04-01 11:00:00',
        'ends_at' => '2026-04-01 11:45:00',
    ]);

    Verbs::commit();

    $events = DB::table('verb_events')
        ->where('type', BookingUpdated::class)
        ->get();

    expect($events)->toHaveCount(1);

    $data = json_decode($events->first()->data, true);
    expect($data['booking_id'])->toBe($booking->id)
        ->and($data['student_id'])->toBe($student->id);
});
