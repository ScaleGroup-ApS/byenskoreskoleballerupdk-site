<?php

namespace App\Actions\Bookings;

use App\Enums\BookingStatus;
use App\Events\BookingCompleted;
use App\Models\Booking;

class CompleteBooking
{
    public function handle(Booking $booking): Booking
    {
        $booking->status = BookingStatus::Completed;
        $booking->save();

        BookingCompleted::fire(
            student_id: $booking->student_id,
            booking_id: $booking->id,
            type: $booking->type->value,
        );

        return $booking->refresh();
    }
}
