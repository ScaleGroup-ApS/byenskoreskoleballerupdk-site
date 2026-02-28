<?php

namespace App\Actions\Enrollment;

use App\Actions\Bookings\CreateBooking;
use App\Enums\BookingType;
use App\Models\Booking;
use App\Models\Enrollment;

class CreateEnrollmentBooking
{
    public function __construct(private readonly CreateBooking $createBooking) {}

    public function handle(Enrollment $enrollment): Booking
    {
        $enrollment->loadMissing(['student', 'course']);

        return $this->createBooking->handle([
            'student_id' => $enrollment->student_id,
            'instructor_id' => null,
            'type' => BookingType::TheoryLesson->value,
            'starts_at' => $enrollment->course->start_at->toDateTimeString(),
            'ends_at' => $enrollment->course->end_at->toDateTimeString(),
        ]);
    }
}
