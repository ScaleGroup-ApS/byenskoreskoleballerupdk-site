<?php

namespace App\Actions\Bookings;

use App\Events\BookingCreated;
use App\Models\Booking;

class CreateBooking
{
    /**
     * @param  array{student_id: int, instructor_id?: int|null, vehicle_id?: int|null, type: string, starts_at: string, ends_at: string, notes?: string|null}  $data
     */
    public function handle(array $data): Booking
    {
        $booking = Booking::create($data);

        BookingCreated::fire(
            student_id: $booking->student_id,
            booking_id: $booking->id,
            type: $booking->type->value,
            starts_at: $booking->starts_at->toDateTimeString(),
        );

        return $booking;
    }
}
